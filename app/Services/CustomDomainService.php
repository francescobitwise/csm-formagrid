<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

final class CustomDomainService
{
    /**
     * @return array{
     *   domain: string,
     *   dns: array{ok: bool, details: string},
     *   http: array{ok: bool, details: string},
     * }
     */
    public function check(string $domain, ?string $expectedTenantId = null): array
    {
        $domain = strtolower(trim($domain));

        $dns = $this->checkDns($domain);
        $http = $this->checkHttp($domain, $expectedTenantId);

        return [
            'domain' => $domain,
            'dns' => $dns,
            'http' => $http,
        ];
    }

    /**
     * DNS is considered OK if:
     * - an A record matches CUSTOM_DOMAIN_TARGET_IP, OR
     * - a CNAME matches CUSTOM_DOMAIN_TARGET_HOST (or points anywhere if target host not set)
     *
     * @return array{ok: bool, details: string}
     */
    private function checkDns(string $domain): array
    {
        $targetIp = trim((string) env('CUSTOM_DOMAIN_TARGET_IP', ''));
        $targetHost = strtolower(trim((string) env('CUSTOM_DOMAIN_TARGET_HOST', '')));

        // dns_get_record may be disabled, but usually works on Linux servers.
        $a = @dns_get_record($domain, DNS_A);
        $cname = @dns_get_record($domain, DNS_CNAME);

        $aIps = [];
        if (is_array($a)) {
            foreach ($a as $row) {
                if (isset($row['ip']) && is_string($row['ip'])) {
                    $aIps[] = $row['ip'];
                }
            }
        }
        $aIps = array_values(array_unique($aIps));

        $cnameTargets = [];
        if (is_array($cname)) {
            foreach ($cname as $row) {
                if (isset($row['target']) && is_string($row['target'])) {
                    $cnameTargets[] = strtolower(rtrim($row['target'], '.'));
                }
            }
        }
        $cnameTargets = array_values(array_unique($cnameTargets));

        $ok = $this->dnsIsOk($targetIp, $targetHost, $aIps, $cnameTargets);
        $details = $this->dnsDetails($targetIp, $targetHost, $aIps, $cnameTargets, $ok);

        return ['ok' => $ok, 'details' => $details];
    }

    /**
     * @return array{ok: bool, details: string}
     */
    private function checkHttp(string $domain, ?string $expectedTenantId): array
    {
        $urls = [
            "https://{$domain}/.well-known/tenant-domain-check",
            "http://{$domain}/.well-known/tenant-domain-check",
        ];

        $result = ['ok' => false, 'details' => 'Non raggiungibile via HTTP/HTTPS (DNS non propagato, Nginx non configurato, o SSL non ancora attivo).'];

        foreach ($urls as $url) {
            $probe = $this->probeTenantContext($url);
            if (! $probe['reachable']) {
                continue;
            }

            if (! $probe['json']) {
                $result = ['ok' => false, 'details' => "Risposta non JSON su {$url}."];
                break;
            }

            $tenantId = $probe['tenant_id'];
            if ($tenantId === '') {
                $result = ['ok' => false, 'details' => "Raggiunge l’app ma tenancy non inizializzata su {$url}."];
                break;
            }

            if ($expectedTenantId !== null && $expectedTenantId !== '' && $tenantId !== $expectedTenantId) {
                $result = ['ok' => false, 'details' => "Raggiunge l’app ma tenant diverso (atteso {$expectedTenantId}, trovato {$tenantId}) su {$url}."];
                break;
            }

            $result = ['ok' => true, 'details' => "OK: l’app risponde e tenancy attiva (tenant_id={$tenantId}) su {$url}."];
            break;
        }

        return $result;
    }

    /**
     * @param  list<string>  $aIps
     * @param  list<string>  $cnameTargets
     */
    private function dnsIsOk(string $targetIp, string $targetHost, array $aIps, array $cnameTargets): bool
    {
        if ($targetIp !== '' && in_array($targetIp, $aIps, true)) {
            return true;
        }

        if ($targetHost !== '' && $cnameTargets !== [] && in_array($targetHost, $cnameTargets, true)) {
            return true;
        }

        return $targetHost === '' && $targetIp === '' && $cnameTargets !== [];
    }

    /**
     * @param  list<string>  $aIps
     * @param  list<string>  $cnameTargets
     */
    private function dnsDetails(string $targetIp, string $targetHost, array $aIps, array $cnameTargets, bool $ok): string
    {
        if ($ok) {
            $msg = null;
            if ($targetIp !== '' && in_array($targetIp, $aIps, true)) {
                $msg = 'Record A OK: punta a '.$targetIp.'.';
            } elseif ($targetHost !== '' && $cnameTargets !== [] && in_array($targetHost, $cnameTargets, true)) {
                $msg = 'Record CNAME OK: punta a '.$targetHost.'.';
            } else {
                $msg = 'Record CNAME trovato: '.implode(', ', $cnameTargets).'.';
            }

            return $msg;
        }

        $observed = $this->formatDnsObserved($aIps, $cnameTargets);
        $prefix = $targetIp !== ''
            ? 'DNS non ancora puntato al server (atteso A='.$targetIp.' o CNAME).'
            : 'DNS non verificabile: imposta CUSTOM_DOMAIN_TARGET_IP (IP VPS) o CUSTOM_DOMAIN_TARGET_HOST (hostname).';

        return $prefix.$observed;
    }

    /**
     * @param  list<string>  $aIps
     * @param  list<string>  $cnameTargets
     */
    private function formatDnsObserved(array $aIps, array $cnameTargets): string
    {
        $parts = [];
        if ($aIps !== []) {
            $parts[] = 'A='.implode(', ', $aIps);
        }
        if ($cnameTargets !== []) {
            $parts[] = 'CNAME='.implode(', ', $cnameTargets);
        }

        return $parts !== []
            ? (' Trovati: '.implode(' | ', $parts).'.')
            : ' Nessun record A/CNAME trovato (o DNS non raggiungibile).';
    }

    /**
     * @return array{reachable: bool, json: bool, tenant_id: string}
     */
    private function probeTenantContext(string $url): array
    {
        $out = ['reachable' => false, 'json' => false, 'tenant_id' => ''];

        try {
            $token = (string) env('CUSTOM_DOMAIN_CHECK_TOKEN', '');
            if ($token === '') {
                return $out;
            }

            $res = Http::timeout(5)
                ->acceptJson()
                ->withHeaders(['X-Tenant-Domain-Check' => $token])
                ->get($url);
            if ($res->ok()) {
                $out['reachable'] = true;

                $json = $res->json();
                if (is_array($json)) {
                    $out['json'] = true;
                    $out['tenant_id'] = (string) ($json['tenant_id'] ?? '');
                }
            }
        } catch (\Throwable $e) {
            // keep defaults
        }

        return $out;
    }
}

