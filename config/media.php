<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Disco per video, SCORM, documenti, copertine corsi
    |--------------------------------------------------------------------------
    |
    | In produzione tipico: "s3". In DB si salvano chiavi oggetto (es. tenants/.../master.m3u8);
    | gli URL pubblici si costruiscono a runtime con MediaStorage::url().
    |
    */
    'disk' => env('MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Visibilità oggetti (soprattutto S3)
    |--------------------------------------------------------------------------
    |
    | Per HLS/SCORM serviti al browser senza URL firmati serve lettura pubblica
    | sugli oggetti o una bucket policy che consenta s3:GetObject sul prefisso
    | usato dall'app (es. tenants/*). Se il bucket ha "Block all public access",
    | l'ACL public-read può fallire: usa policy sul bucket e eventualmente
    | MEDIA_UPLOAD_VISIBILITY=private.
    |
    */
    'upload_visibility' => env('MEDIA_UPLOAD_VISIBILITY', 'public'),

    /*
    |--------------------------------------------------------------------------
    | ACL su oggetti S3 (PutObject)
    |--------------------------------------------------------------------------
    |
    | Con "Bucket owner enforced" / ACL disattivate, inviare visibility/ACL
    | fa fallire l'upload. Default: false = nessun ACL (usa la policy sul bucket).
    | Imposta true solo se il bucket accetta ancora ACL (es. public-read).
    |
    */
    's3_put_acl' => env('MEDIA_S3_PUT_ACL', false),

    /*
    |--------------------------------------------------------------------------
    | URL firmati per oggetti statici (copertine, poster, PDF pubblici)
    |--------------------------------------------------------------------------
    |
    | Con bucket privato (o "Block public access") Storage::url() punta a oggetti
    | non leggibili → immagini rotte in admin/catalogo. true = presigned GetObject.
    | Auto (null): firmati se MEDIA_UPLOAD_VISIBILITY=private oppure MEDIA_DISK=s3
    | senza ACL in upload (tipico bucket "Bucket owner enforced").
    |
    */
    'signed_object_urls' => env('MEDIA_SIGNED_OBJECT_URLS'),

    'signed_object_ttl_minutes' => max(5, min(1440, (int) env('MEDIA_SIGNED_OBJECT_TTL_MINUTES', 120))),

    /*
    |--------------------------------------------------------------------------
    | HLS per learner: manifest autenticato
    |--------------------------------------------------------------------------
    |
    | Se true e MEDIA_DISK=s3, il player usa una rotta tenant (auth + iscrizione) che riscrive
    | il .m3u8. I segmenti/sotto-playlist dipendono da MEDIA_HLS_SEGMENT_DELIVERY.
    |
    */
    'signed_hls_manifest' => env('MEDIA_SIGNED_HLS_MANIFEST', false),

    'signed_hls_ttl_minutes' => max(1, min(240, (int) env('MEDIA_SIGNED_HLS_TTL_MINUTES', 90))),

    /*
    |--------------------------------------------------------------------------
    | Consegna segmenti HLS (dopo manifest autenticato)
    |--------------------------------------------------------------------------
    |
    | auto  = locale/dev → proxy same-origin; in prod → CDN (AWS_URL) se presente, altrimenti proxy
    | proxy = sempre proxy app (affidabile, zero CORS; più carico server)
    | cdn   = URL su CloudFront/AWS_URL (leggero; richiede CORS del dominio tenant sul CDN)
    |
    */
    'hls_segment_delivery' => env('MEDIA_HLS_SEGMENT_DELIVERY', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Binario ffprobe (durata video automatica)
    |--------------------------------------------------------------------------
    |
    | Lascia vuoto per usare "ffprobe" dal PATH. Su Windows puoi impostare
    | il percorso assoluto, es. C:\ffmpeg\bin\ffprobe.exe
    |
    */
    'ffprobe_path' => env('MEDIA_FFPROBE_PATH', ''),

];
