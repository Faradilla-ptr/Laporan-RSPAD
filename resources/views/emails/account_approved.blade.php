<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Disetujui - RSPAD Gatot Soebroto</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 20px;
            color: #333333;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }
        .header-banner {
            background: linear-gradient(135deg, #23422E 0%, #2E5A3C 100%);
            padding: 30px 25px;
            text-align: center;
            color: #ffffff;
        }
        .header-banner h2 {
            margin: 0 0 5px 0;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .header-banner p {
            margin: 0;
            font-size: 13px;
            opacity: 0.9;
        }
        .content-body {
            padding: 30px 25px;
        }
        .status-badge {
            display: inline-block;
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 20px;
            border: 1px solid #a5d6a7;
        }
        .credentials-box {
            background-color: #f8fafc;
            border: 1.5px dashed #cbd5e1;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .credentials-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        .credentials-row:last-child {
            border-bottom: none;
        }
        .cred-label {
            color: #64748b;
            font-weight: 600;
        }
        .cred-value {
            color: #0f172a;
            font-weight: 700;
            font-family: monospace;
        }
        .btn-login {
            display: block;
            width: 200px;
            margin: 25px auto 10px auto;
            padding: 12px 20px;
            background: linear-gradient(135deg, #2E5A3C 0%, #3B6E4A 100%);
            color: #ffffff !important;
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 4px 10px rgba(46, 90, 60, 0.2);
        }
        .footer {
            background-color: #f1f5f9;
            padding: 15px 25px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header-banner">
            <h2>RSPAD GATOT SOEBROTO</h2>
            <p>Subdit Pelaporan Medis - Sistem Pelaporan Rawat Jalan</p>
        </div>

        <div class="content-body">
            <div class="status-badge">✓ AKUN DISETUJUI / AKTIF</div>

            <p>Yth. <strong>{{ $user->name }}</strong>,</p>
            <p>Selamat! Pendaftaran akun Petugas Pelaporan Anda telah <strong>disetujui (divalidasi)</strong> oleh Admin (Kaur). Akun Anda kini telah aktif dan dapat digunakan untuk masuk ke sistem.</p>

            <div class="credentials-box">
                <div style="font-weight: 700; font-size: 13px; color: #1e293b; margin-bottom: 12px;">DETAIL KREDENSIAL LOGIN AKUN ANDA:</div>
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: 600;">Nama Lengkap:</td>
                        <td style="padding: 6px 0; color: #0f172a; font-weight: 700; text-align: right;">{{ $user->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: 600;">Email Login:</td>
                        <td style="padding: 6px 0; color: #0f172a; font-weight: 700; text-align: right;">{{ $user->email }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: 600;">Kata Sandi Default:</td>
                        <td style="padding: 6px 0; color: #2E5A3C; font-weight: 700; font-family: monospace; text-align: right; font-size: 15px;">{{ $defaultPassword }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: 600;">Peran Akses:</td>
                        <td style="padding: 6px 0; color: #0f172a; font-weight: 700; text-align: right;">Petugas Input SIMRS</td>
                    </tr>
                </table>
            </div>

            <p style="font-size: 13px; color: #475569;">
                Silakan gunakan email dan kata sandi default di atas untuk login ke portal sistem. Anda disarankan untuk segera memperbarui kata sandi Anda setelah berhasil login.
            </p>

            <a href="{{ route('login') }}" class="btn-login" target="_blank">Masuk ke System &rarr;</a>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Subdit Pelaporan Medis RSPAD Gatot Soebroto.<br>
            Pesan otomatis dari Sistem Informasi Rekapitulasi SIMRS & Puskesad.
        </div>
    </div>
</body>
</html>
