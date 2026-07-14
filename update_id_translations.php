<?php

$idTranslations = [
    "Action redone." => "Aksi diulangi.",
    "Action undone." => "Aksi dibatalkan.",
    "Applying..." => "Menerapkan...",
    "Are you sure you want to permanently delete this note? All sub-tabs will also be deleted." => "Apakah Anda yakin ingin menghapus catatan ini secara permanen? Semua sub-tab juga akan dihapus.",
    "At least one narrative section is required." => "Setidaknya satu bagian naratif diperlukan.",
    "Back to top" => "Kembali ke atas",
    "Background" => "Latar Belakang",
    "Chapter Cover" => "Sampul Bab",
    "Complete Your Profile - Spindle" => "Lengkapi Profil Anda - Spindle",
    "Crop Preview" => "Pratinjau Potongan",
    "Didn't receive the email yet?" => "Belum menerima email?",
    "Drop your email, and we'll send a magic link to help you write the next line." => "Masukkan email Anda, dan kami akan mengirim tautan ajaib untuk membantu Anda menulis kalimat berikutnya.",
    "Email" => "Email",
    "Emoji" => "Emoji",
    "Instagram" => "Instagram",
    "Leaving the writer's desk? Your draft will wait here." => "Meninggalkan meja penulis? Draf Anda akan menunggu di sini.",
    "Let's set up your creative identity." => "Mari atur identitas kreatif Anda.",
    "Looks like it's a bit quiet in here..." => "Sepertinya agak sepi di sini...",
    "No results found" => "Tidak ada hasil ditemukan",
    "Note created." => "Catatan dibuat.",
    "Note deleted." => "Catatan dihapus.",
    "Note duplicated." => "Catatan diduplikasi.",
    "Note moved." => "Catatan dipindahkan.",
    "Note renamed." => "Catatan diganti nama.",
    "Picture" => "Gambar",
    "Preview" => "Pratinjau",
    "Relations" => "Hubungan",
    "Section" => "Bagian",
    "Sign In - Spindle" => "Masuk - Spindle",
    "Sign Up - Spindle" => "Daftar - Spindle",
    "Spindle dashboard preview" => "Pratinjau dasbor Spindle",
    "Spindle — Spin A Yarn" => "Spindle — Rajut Sebuah Cerita",
    "Sub tab created." => "Sub tab dibuat.",
    "Switch to Dark Mode" => "Beralih ke Mode Gelap",
    "Switch to Light Mode" => "Beralih ke Mode Terang",
    "Tag..." => "Tag...",
    "Template Preview" => "Pratinjau Templat",
    "Text edited." => "Teks diedit.",
    "The Great Tangle" => "The Great Tangle",
    "The Spindle" => "The Spindle",
    "The Weaver" => "The Weaver",
    "The cover image must be a valid image file." => "Gambar sampul harus berupa file gambar yang valid.",
    "The cover image size must not exceed 5MB." => "Ukuran gambar sampul tidak boleh lebih dari 5MB.",
    "The section title is required." => "Judul bagian wajib diisi.",
    "The section title must not be greater than 40 characters." => "Judul bagian tidak boleh lebih dari 40 karakter.",
    "The selected file type is not supported. Please upload a JPG, PNG, SVG, or WEBP." => "Tipe file tidak didukung. Harap unggah JPG, PNG, SVG, atau WEBP.",
    "The selected image is too large. The maximum allowed file size is 2MB." => "Gambar yang dipilih terlalu besar. Ukuran file maksimum adalah 2MB.",
    "The structure name is required." => "Nama struktur wajib diisi.",
    "The structure name must not be greater than 100 characters." => "Nama struktur tidak boleh lebih dari 100 karakter.",
    "This template cannot be deleted because it is currently being used by one or more of your projects. You must change the structure in those projects first." => "Templat ini tidak dapat dihapus karena sedang digunakan oleh proyek Anda. Anda harus mengubah struktur di proyek tersebut terlebih dahulu.",
    "Toggle Theme" => "Ubah Tema",
    "Understood" => "Mengerti",
    "Unknown" => "Tidak diketahui",
    "User" => "Pengguna",
    "We've sent the magic link to <strong>:email</strong>." => "Kami telah mengirim tautan ajaib ke <strong>:email</strong>.",
    "You Didn't Have Any Chapters!" => "Anda Belum Memiliki Bab!",
    "You Didn't Have Any Characters!" => "Anda Belum Memiliki Karakter!",
    "You Didn't Have Any Notes!" => "Anda Belum Memiliki Catatan!",
    "You Didn't Have Any Project!" => "Anda Belum Memiliki Proyek!",
    "You must agree to the terms and conditions." => "Anda harus menyetujui syarat dan ketentuan.",
    "Your masterpiece is secured. The password has been rewritten." => "Karya Anda aman. Kata sandi telah ditulis ulang.",
    "Zoom In" => "Perbesar",
    "Zoom Out" => "Perkecil",
    "cover image" => "gambar sampul",
    "description" => "deskripsi",
    "e.g. My Hero's Journey" => "contoh: Perjalanan Pahlawanku",
    "narrative sections" => "bagian naratif",
    "section :num description" => "deskripsi bagian :num",
    "section :num title" => "judul bagian :num",
    "structure name" => "nama struktur"
];

$idFile = __DIR__ . '/lang/id.json';
$data = json_decode(file_get_contents($idFile), true) ?: [];

foreach ($idTranslations as $en => $id) {
    if (isset($data[$en])) {
        $data[$en] = $id;
    }
}

file_put_contents($idFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "id.json updated successfully.\n";

