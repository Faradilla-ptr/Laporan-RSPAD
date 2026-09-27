<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Akun Ditolak - RSPAD Gatot Soebroto</title>
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
            background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 100%);
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
        .status-badge-rejected {
            display: inline-block;
            background-color: #fef2f2;
            color: #991b1b;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
        }
        .info-box {
            background-color: #fff5f5;
            border-left: 4px solid #ef4444;
            padding: 15px 20px;
            border-radius: 4px;
            margin: 20px 0;
            font-size: 13.5px;
            color: #7f1d1d;
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
            <div class="status-badge-rejected">✕ PENDAFTARAN TIDAK DISETUJUI</div>

            <p>Yth. <strong>{{ $user->name }}</strong>,</p>
            <p>Terima kasih telah mengajukan pendaftaran akun Petugas pada Sistem Rekapitulasi Pelaporan SIMRS RSPAD Gatot Soebroto.</p>
            
            <div class="info-box">
                Mohon maaf, pengajuan pendaftaran akun Anda dengan email <strong>{{ $user->email }}</strong> <strong>belum dapat disetujui / ditolak</strong> oleh Admin (Kaur) Sistem.
            </div>

            <p style="font-size: 13.5px; color: #475569;">
                Apabila Anda merasa terdapat kesalahan atau membutuhkan bantuan lebih lanjut mengenai akses akun Petugas, silakan hubungi pihak Kaur / Admin Pelaporan Medis RSPAD Gatot Soebroto.
            </p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Subdit Pelaporan Medis RSPAD Gatot Soebroto.<br>
            Pesan otomatis dari Sistem Informasi Rekapitulasi SIMRS & Puskesad.
        </div>
    </div>
</body>
</html>
