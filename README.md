<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Kelompok Project App Gym 

## CHOIRUL RIZQY AGUNG PRASETYO - G.111.22.0002
## NASRUL LATIEF - G.111.22.0015
## RAFI HANA PRASETYO - G.111.22.0044
## BAGUS RIYAN SAPUTRO - G.111.22.0049

## Tutorial Run Program APPGYM

<P> Sistem Gym yang memakai kartu untuk melakukan check in dan check out sebagai M2M utama </p>

Untuk menjalankan program harus menginstall beberapa tools dan migrate Seeder dari paket gym dan superadmin untuk akses awal dan generate api key.
ganti menjadi .env
buat juga database dan konfigurasikan dengan .env 
jangan lupa untuk menghapus env.example. // hapus example sisakan env. saja

php artisan composer install
php artisan composer update
php artisan db:seed --class=PaketGym
php artisan db:seed --classSuper=AdminSeeder
php artisan app:generate-api-key "Device Admin"
php artisan migrate
php artisan serve


 




POST http://127.0.0.1:8000/api/v1/auth/login 
 ## Header
    accept Application/json
    content/type Application/json

## Body jika menggunakan postman taruh di dalam RAW
## Login SuperAdmin
{
    "name": "Superadmin",
    "email": "superadmin@coboy.com",
    "password": "12345678",
    "role" : "superadmin"
}

setelah login dengan superadmin langkah selanjutnya adalah dengan membuat register admin dengan route api sebagai berikut
POST http://127.0.0.1:8000/api/v1/auth/register/admin
## Header
     accept Application/json
     content/type Application/json 
     Authorization Bearer "Token yang kamu dapat dari hasil login"

## Register Admin 
{
    "name": "admin1",
    "email": "admin1@coboy.com",
    "password": "12345678",
    "password_confirmation": "12345678",
    "role": "admin",
}

Setelah berhasil maka kita bisa login dengan menggunakan admin yang sudah di buat dengan akun superadmin tadi dan route login masih sama dengan route login yang superadmin gunakan
## Login Admin  
POST http://127.0.0.1:8000/api/v1/auth/login 
    header
    accept Application/json
    content/typw Application

## Body
{
    "name": "admin1",
    "email": "admin1@coboy.com",
    "password": "12345678",
    "role" : "admin"
} 

Setelah login dengan akun admin kita bisa melakukan langkah selanjutnya dengan register member yang akan mendaftar dengan langkah sebagai berikut alert!!! "kita hanya bisa mendaftarkan member dengan menggunakan akun admin atau superadmin"

## Register Member

POST http://127.0.0.1:8000/api/v1/auth/register/member
     header 
     accept Application/json
     content/type Application/json 
     Authorization Bearer "Token yang kamu dapat dari hasil login admin"

## Body    
    {
        "name": "member",
        "email": "member@coboy.com",
        "phone": "123456789",  
        "membership_type": "bronze",
        "payment_method": "cash"
    }

Setelah membuat member kita bisa login dengan member dengan akun member yang sudah di buat oleh admin. Allert!!! "password yang di gunakan untuk login member adalah menggunakan 6 digit akhir dari nomer telephone yang di daftarkan oleh member"

## Login Member 
POST http://127.0.0.1:8000/api/v1/auth/login

## Header 
    accept Application/json
    content/type Application/json

## Body 
{
    "name": "member",
    "email": "member@coboy.com",
    "password": "456789",
    "role": "member"
}

Setelah login dengan member kita akan mendapatkan kartu member yang sudah kita buat methodnya di MemberController dengan nama RF_id_card
dan kita akan melakukan bagian paling pentig yang akan membuat App ini menggunakan konsep machine to machine dengan tap in kartu agar member bisa di katakan hadir di sesigym. pertama tama kita akan melakukan generate api key untuk akses kartu tersebut ke server dengan langkah. "rfid_card": "RFID-67605F2AC9F9E" itu adalah hasil dari kita mendaftarkan member akan muncul di bagian json
php artisan app:generate-api-key "Device Admin" dengan kita menyimpannya dulu di notepad.

Sebelum memulai test pergi ke Config/services.php lihat di bagian ini
     'device' => [
        'api_key' => 'wJNnxHjmVrd1BDQHon6SwE7IWHHeuSQs', // ubah menjadi api key yang sudah kalian generate tadi
    ],

Tambahkan ini juga di env.
kalian bisa menaruhnya di bagian paling bawah
    API_KEY="wJNnxHjmVrd1BDQHon6SwE7IWHHeuSQs" // // ubah menjadi api key yang sudah kalian generate tadi

## Check-in Kartu Member 
POST http://127.0.0.1:8000/api/v1/device/check-in
    Header 
    x-api-key wJNnxHjmVrd1BDQHon6SwE7IWHHeuSQs
    X-Requested-With RFID Device Admin
    content/type application/json

## Body
    {
        "rfid_card": "RFID-67605F2AC9F9E" // di bagian data base kalian melihat di tabel member cari di cari kolom rfid_card_number
    }

Setelah kita melakukan check-in kita bisa check-out setelah member menyelesaikan kegiatannya di gym

## Check-out kartu Member
POST http://127.0.0.1:8000/api/v1/device/check-out
## Header 
    x-api-key wJNnxHjmVrd1BDQHon6SwE7IWHHeuSQs
    X-Requested-With RFID Device Admin
    content/type application/json

## Body
    {
        "rfid_card": "RFID-6763C0FBE580C"
    }
ZapaC8g4IRTMyxvzdqd1WZpc8KogCAyC