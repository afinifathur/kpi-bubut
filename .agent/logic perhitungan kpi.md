# Penjelasan Logika Perhitungan Rata-rata KPI Harian Operator

Dalam laporan harian KPI, Anda mungkin melihat perbedaan antara "Rata-rata Persentase" yang dihitung secara manual (dibagi jumlah antrian) dengan "Rata-rata Harian" (akumulasi performa) yang sebenarnya tertera di sistem.

Agar tidak terjadi kesalahpahaman saat ada operator yang dipanggil oleh HR karena salah satu produksinya memiliki KPI rendah, berikut adalah penjelasan detail mengenai cara sistem mengkalkulasi angka **Rata-rata KPI Harian**.

## Studi Kasus
Sebagai contoh, kita ambil data 4 pengerjaan benda kerja / antrian dari operator **Yunus Santoso** di satu hari kerja:

| Item / Aktivitas | Target (Pcs) | Aktual (Pcs) | KPI (Aktual / Target) |
| :--- | :---: | :---: | :---: |
| SS316 CASTED PLANE FLANGE (DN 32) | 3 | 8 | 266.67% |
| SS304 CASTED RAISED FLANGE (1/2") | 20 | 22 | 110.00% |
| SS304 CASTED PLANE FLANGE (DN 20) | 115 | 158 | 137.39% |
| SS304 CASTED SPECIAL RAISED FLANGE (DN 50) | 56 | 42 | 75.00% |

Dari pengerjaan di atas, terlihat ada 1 pengerjaan yang tidak mencapai target yaitu **75.00%**. 

---

## ❌ Kesalahan Umum: Menghitung "Rata-rata Biasa"

Jika HR atau manajemen hanya menjumlahkan seluruh persentase KPI kemudian membaginya rata dengan jumlah item (4 baris), hasilnya adalah:
` ( 266.67% + 110.00% + 137.39% + 75.00% ) / 4 ` = **147.26%**

**Mengapa metode ini SALAH dan TIDAK ADIL?**
Sebab, metode tersebut menganggap setiap pengerjaan benda memiliki "bobot" yang sama ratanya. Padahal faktanya, kuantitas item pertama (Target 3 pcs) jauh lebih sedikit ketimbang item ketiga (Target 115 pcs).

Jika menggunakan rata-rata biasa, persentase super tinggi dari item yang hanya diproduksi sangat sedikit (266% dari target 3) akan "terlalu melambungkan" persentase akhir operator secara ekstrim, sehingga tidak mencerminkan total beban kerja aslinya. Begitu pula sebaliknya, gagal sedikit saja di item dengan target kecil bisa menghancurkan pencapaian persentase item lain yang skalanya jauh lebih besar.

---

## ✅ Cara Sistem Bekerja: "Rata-rata Berbobot" (Weighted Average)

Agar metrik penilaian benar-benar representatif, adil dan proporsional dengan **total beban pekerjaan / volume kerja**, sistem tidak menggunakan angka dari kolom KPI di paling kanan. Melainkan, sistem akan menghimpun komparasi Total Output (Aktual) operator diakumulasikan terhadap Total Kewajiban (Target) di hari tersebut.

Cara kerjanya (sesuai contoh),
**Total Kewajiban (Target Harian) =**
`3 + 20 + 115 + 56` = **194 Pcs.**

**Total Output (Aktual Harian) =**
`8 + 22 + 158 + 42` = **230 Pcs.**

Maka **Persentase KPI Harian Operator** adalah perbandingan dari akumulasi di atas:
= `( Total Aktual / Total Target ) × 100%`
= `( 230 / 194 ) × 100%`
= **118.5567...%**
= Dibulatkan menjadi: **118.56%**

Angka **118.56%** inilah yang menjadi bukti akurat atas performa murni operator pada keseluruhan waktu berjalannya jam shift produksi (bobot terpusat).

---

## Bagaimana Menjelaskan hal tersebut pada HRD?

**Jika ditanya bagaimana logika KPI-nya bekerja:**
> *"Bapak/Ibu, KPI Rata-rata Harian di sistem (118.56%) ini tidak dihitung dengan menjumlahkan dan membagi persentase satu persatu per item pengerjaan. Melainkan, dihitung dari Total Bobot Pengerjaannya secara Kumulatif."*
>
> *"Secara sederhana, dalam satu hari operator X diwajibkan oleh perusahaan secara total untuk menyelesaikan 194 buah part. Pada riil lapangannya, ia berhasil melampauinya dengan menyelesaikan 230 buah part. Karena jumlah outputnya (230) melampaui total beban target harian mesin yang harus ia selesaikan (194), maka secara performa ia berhasil melampaui KPI sebanyak 18% di atas ekspektasi normal (118.56%)."*
>
> *"Oleh karena itu, jika beliau memiliki kekurangan 14 buah part (mendapat nilai 75%) di salah satu jam kerjanya (DN 50), hal ini bisa di-"cover" oleh usahanya menyerap beban lebih (surplus output 43 buah part (137.39%)) dari antrian produk sebelumnya (DN 20), yang ukurannya memakan porsi dominan dari jatah produksi harian. Karenanya secara timbangan kerja harian, performanya masih sangat bagus."*

**Secara konklusi, cara penilaian dengan Total Target & Aktual (Weighted Average) sangat melindungi hak dan keadilan para pekerja di lapangan.** Pekerja tidak serta merta dimarahi jika mereka gagal pada satu item kecil di jam tertentu, asalkan pada jam-jam lainnya mereka dapat menunjukkan progres surplus kapasitas yang besar untuk secara sadar menutup kekurangannya di hari yang sama.
