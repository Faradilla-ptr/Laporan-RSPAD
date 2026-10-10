<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware untuk menyuntikkan HTTP Security Headers pada setiap respon server.
 * Melindungi aplikasi dari serangan Clickjacking, MIME-Sniffing, XSS (Cross-Site Scripting),
 * dan pembatasan fitur perangkat melalui Permissions-Policy & Content Security Policy (CSP).
 */
class SecurityHeaders
{
    /**
     * Memproses request HTTP yang masuk dan menambahkan header keamanan pada objek Response.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Lanjutkan request ke controller atau middleware berikutnya untuk mendapatkan response
        $response = $next($request);

        // Mencegah Clickjacking: halaman hanya boleh dimuat dalam iframe dari origin yang sama
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Mencegah MIME Type Sniffing: browser wajib mematuhi header Content-Type yang dikirim server
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Mengaktifkan filter XSS bawaan browser jika terjadi serangan injeksi script
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Membatasi informasi perujuk (referrer) saat pengguna berpindah domain
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Membatasi akses API browser terhadap fitur sensitif (kamera, mikrofon, lokasi, sistem pembayaran)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // Content Security Policy (CSP): Mengatur sumber domain tepercaya untuk script, stylesheet, font, dan gambar
        $csp = "default-src 'self'; ".
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; ".
               "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; ".
               "font-src 'self' https://fonts.gstatic.com data:; ".
               "img-src 'self' data: blob:; ".
               "connect-src 'self'; ".
               "frame-ancestors 'self';";

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
