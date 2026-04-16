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
    | HLS per learner: manifest con segmenti presigned (S3)
    |--------------------------------------------------------------------------
    |
    | Se true e MEDIA_DISK=s3, il player usa una rotta tenant (auth + iscrizione) che riscrive
    | il .m3u8 con URL S3 temporanei per ogni segmento (TTL sotto). Il link diretto CDN al manifest
    | non basta da solo: senza sessione non si ottiene il manifest riscritto.
    |
    | Nota: se CloudFront/S3 consentono ancora GET anonimi sul prefisso video-hls, chi conosce
    | le chiavi oggetto può comunque richiederle; per hardening restringi il prefisso in bucket/CF.
    |
    */
    'signed_hls_manifest' => env('MEDIA_SIGNED_HLS_MANIFEST', false),

    'signed_hls_ttl_minutes' => max(1, min(240, (int) env('MEDIA_SIGNED_HLS_TTL_MINUTES', 90))),

];
