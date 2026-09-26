import os
import xlrd
import sqlite3
import datetime

db_path = os.path.abspath("database/database.sqlite")

def derive_kelompok(jenis_penjamin, pangkat, instansi, kategori, kesatuan):
    p   = str(jenis_penjamin or '').strip().upper()
    pa  = str(pangkat or '').strip().upper()
    ins = str(instansi or '').strip().upper()
    kat = str(kategori or '').strip().upper()
    kes = str(kesatuan or '').strip().upper()

    if 'PBI' in p:
        return 'BPJS PBI'
    elif 'MANDIRI' in p or 'SWASTA' in p:
        return 'BPJS MANDIRI / SWASTA'
    elif 'MILITER' in p or 'DINAS' in p or 'MILITER' in kat or pa != '':
        if 'KELUARGA' in kat or 'KELUARGA' in p or 'ISTRI' in kat or 'ANAK' in kat:
            return 'KELUARGA MILITER'
        else:
            return 'MILITER TNI AD'
    elif 'PNS' in p or 'PNS' in kat or 'KEMHAN' in ins or 'TNI' in ins:
        if 'KELUARGA' in kat or 'KELUARGA' in p or 'ISTRI' in kat or 'ANAK' in kat:
            return 'KELUARGA PNS'
        else:
            return 'PNS KEMHAN/TNI'
    elif 'PURNA' in p or 'PURNA' in kat:
        return 'PURNAWIRAWAN'
    else:
        return 'UMUM / TUNAI'

print("==================================================")
print("     IMPORTING REAL 3 MONTHS DATA (JUN, JUL, AUG) ")
print("==================================================")

folders = [
    ('rspad-file/JUNI', 6, 2026, 'Juni 2026'),
    ('rspad-file/B. URO', 7, 2026, 'Juli 2026'),
    ('rspad-file/AGUSTUS', 8, 2026, 'Agustus 2026'),
]

# Connect via PHP/Artisan or sqlite / mysql
# We will write a PHP script to perform clean Eloquent insertion so it works on whatever DB is configured (.env)!
