## Fix

1. Validasi hanya di frontend

    Berada pada berkas `app/Http/Controllers/CourseController.php` Fungsi `store` di controller langsung menyimpan data dari permintaan ($request) pengguna ke database tanpa melakukan validasi terlebih dahulu di sisi server (backend). Pada berkas `app/Http/Controllers/CourseController.php` (pada `method store`). Dampak nya Dari Sudut Pandang Pengguna (User) yaitu Jika ada kegagalan pada skrip frontend di browser, pengguna tidak akan mendapatkan pesan error yang jelas dari server saat menginput data yang salah. Dari Sudut Pandang Penyerang (Attacker): Penyerang dapat dengan mudah mematikan JavaScript, mengedit batas formulir melalui Inspect Element, atau mengirimkan request langsung menggunakan alat seperti `Postman/cURL`. Ketiadaan validasi server membuat penyerang bisa memasukkan data rusak, data bernilai kosong `null`, atau nilai yang tidak masuk akal (misalnya SKS bernilai -999 atau lecturer_id yang tidak ada) langsung ke database. 

    Kode sebelum perbaikan : 
    ```
    public function store(Request $request)
    {
    // SALAH: Langsung menyimpan input tanpa adanya $request->validate()
    Course::create([
        'code'        => $request->code,
        'name'        => $request->name,
        'description' => $request->description,
        'sks'         => $request->sks,
        'lecturer_id' => $request->lecturer_id,
        'status'      => $request->status ?? 'draft',
    ]);

    $courses = Course::paginate(10);

    return view('courses.index', compact('courses'));
    }
    ```

    Kode setelah perbaikan :
    ```
    public function store(Request $request)
    {
    // BENAR: Memeriksa dan memfilter seluruh input di sisi server sebelum disimpan
    $validated = $request->validate([
        'code'        => 'required|string|max:20|unique:courses,code',
        'name'        => 'required|string|max:255',
        'description' => 'nullable|string',
        'sks'         => 'required|integer|min:1|max:6',
        'lecturer_id' => 'required|exists:users,id',
        'status'      => 'required|in:draft,active,archived',
    ]);

    // Hanya data yang lolos validasi yang akan dimasukkan ke database
    Course::create($validated);

    return redirect()->route('courses.index')->with('success', 'Mata kuliah berhasil ditambahkan.');
    }
    ```

2. Unique pada update yang menolak dirinya sendiri

    Pada fungsi update, aturan validasi keunikan `unique:courses,code` mengecek seluruh baris di database tanpa mengecualikan data yang sedang diperbarui. Hal ini membuat server menolak proses simpan karena menganggap kode mata kuliah tersebut sudah terpakai (oleh data itu sendiri). Diberkas `app/Http/Controllers/CourseController`.php (pada method update). Dampak nya Dari Sudut Pandang Pengguna (User): Mengganggu fungsi dasar aplikasi (denial of function bagi pengguna sah). Pengguna tidak dapat memperbarui informasi lain pada mata kuliah tersebut (misalnya hanya ingin mengubah Nama atau SKS) tanpa harus merubah Kode Mata Kuliahnya. Dari Sudut Pandang Penyerang (Attacker): Meskipun tidak membuka celah peretasan data secara langsung, bug logika ini dapat dimanfaatkan untuk menggagalkan pembaruan data sistemik atau menyebabkan kebingungan operasional bagi pengelola aplikasi. 

    Kode sebelum perbaikan : 
    ```
    public function update(Request $request, Course $course)
    {
    $validated = $request->validate([
        // SALAH: Mengecek keunikan ke seluruh tabel tanpa mengecualikan ID milik $course
        'code'        => 'required|string|max:20|unique:courses,code',
        'name'        => 'required|string|max:255',
        'description' => 'nullable|string',
        'sks'         => 'required|integer|min:1|max:6',
        'lecturer_id' => 'required|exists:users,id',
        'status'      => 'required|in:draft,active,archived',
    ]);

    $course->update($validated);

    return redirect()->route('courses.show', $course)->with('success', 'Mata kuliah berhasil diperbarui.');
    }
    ```

    Kode setelah perbaikan 
    ```
    use Illuminate\Validation\Rule; // Jangan lupa tambahkan import Rule di bagian atas file

    public function update(Request $request, Course $course)
    {
    // BENAR: Menggunakan Rule::unique()->ignore($course->id)
    // agar validator mengabaikan ID mata kuliah yang sedang di-edit ini
    $validated = $request->validate([
        'code' => [
            'required',
            'string',
            'max:20',
            Rule::unique('courses', 'code')->ignore($course->id),
        ],
        'name'        => 'required|string|max:255',
        'description' => 'nullable|string',
        'sks'         => 'required|integer|min:1|max:6',
        'lecturer_id' => 'required|exists:users,id',
        'status'      => 'required|in:draft,active,archived',
    ]);

    $course->update($validated);

    return redirect()->route('courses.show', $course)->with('success', 'Mata kuliah berhasil diperbarui.');
    }
    ```

3. Filter disimpan di session 

    Fungsi pencarian dan penyaringan data (filter) menyimpan nilai kata kunci pencarian dan status ke dalam `session()` server, bukan membacanya langsung dari parameter URL (query string). Diberkas `app/Http/Controllers/CourseController.php` (pada method index). Dampak Dari Sudut Pandang Pengguna (User): Merusak pengalaman penggunaan aplikasi (UX). Jika pengguna membuka dua tab browser sekaligus dengan filter yang berbeda, filter pada Tab A akan tertimpa oleh Tab B. Selain itu, saat pengguna meninggalkan aplikasi dan kembali di kemudian hari, filter lama masih tertinggal dan membingungkan karena halaman tidak menampilkan seluruh data secara normal. Dari Sudut Pandang Penyerang (Attacker): Menyimpan state pencarian pada session membuat aplikasi menjadi stateful tanpa alasan yang jelas. Penyerang dapat memanipulasi session atau memanfaatkan perilaku persisten ini untuk memicu bug logika jika dipadukan dengan celah keamanan lain (seperti Session Fixation).

    Kode sebelum perbaikan : 
    ```
    public function index(Request $request)
    {
    // SALAH: Memasukkan dan membaca kata kunci filter dari Session server
    if ($request->has('search')) {
        session(['course_search' => $request->search]);
    }
    if ($request->has('status')) {
        session(['course_status' => $request->status]);
    }

    $search = session('course_search');
    $status = session('course_status', 'active');

    $query = Course::with('lecturer')->withCount('students');

    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%'.$search.'%')
              ->orWhere('code', 'like', '%'.$search.'%');
        });
    }

    if ($status) {
        $query->where('status', $status);
    }

    $courses = $query->paginate(10);

    return view('courses.index', compact('courses'));
    }
    ```

    Kode setelah perbaikan : 
    ```
    public function index(Request $request)
    {
    // BENAR: Membaca parameter langsung dari $request URL (tanpa menyimpan di Session)
    $query = Course::with('lecturer')->withCount('students');

    if ($request->filled('search')) {
        $query->where(function ($q) use ($request) {
            $q->where('name', 'like', '%'.$request->search.'%')
              ->orWhere('code', 'like', '%'.$request->search.'%');
        });
    }

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    // Menggunakan ->withQueryString() agar parameter filter tetap terbawa di pagination URL
    $courses = $query->paginate(10)->withQueryString();

    return view('courses.index', compact('courses'));
    }
    ```
4. Pagination kehilangan query string

    Pada fungsi index, pagination tidak mempertahankan parameter pencarian dan filter dari URL. Ketika pengguna berpindah ke halaman berikutnya, parameter seperti search dan status tidak ikut terbawa. Masalah ini terdapat di berkas `app/Http/Controllers/CourseController.php`, pada method index.
    Dampak Dari Sudut Pandang Pengguna (User): Pengguna yang sedang melakukan pencarian atau filter akan kehilangan filter tersebut ketika berpindah ke halaman berikutnya. Misalnya, pengguna mencari mata kuliah dengan kata kunci "Basis Data" lalu membuka halaman 2, hasil pada halaman 2 dapat menampilkan seluruh mata kuliah karena parameter pencarian tidak ikut terbawa.
    Dari Sudut Pandang Penyerang (Attacker): Masalah ini tidak secara langsung memberikan akses tambahan kepada penyerang untuk mendapatkan data yang seharusnya tidak boleh diakses. Namun, hilangnya query string dapat menyebabkan hasil pencarian tidak konsisten dengan permintaan awal pengguna.

    Kode sebelum perbaikan : 
    ```
    public function index(Request $request)
    {
    $query = Course::with('lecturer')->withCount('students');

    if ($request->filled('search')) {
        $query->where(function ($q) use ($request) {
            $q->where('name', 'like', '%'.$request->search.'%')
              ->orWhere('code', 'like', '%'.$request->search.'%');
        });
    }

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    // SALAH: Query string tidak ikut terbawa saat berpindah halaman
    $courses = $query->paginate(10);

    return view('courses.index', compact('courses'));
    }
    ```

    Kode setelah perbaikan :
    ```
    public function index(Request $request)
    {
    $query = Course::with('lecturer')->withCount('students');

    if ($request->filled('search')) {
        $query->where(function ($q) use ($request) {
            $q->where('name', 'like', '%'.$request->search.'%')
              ->orWhere('code', 'like', '%'.$request->search.'%');
        });
    }

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    // BENAR: Query string tetap dibawa ke halaman berikutnya
    $courses = $query->paginate(10)->withQueryString();

    return view('courses.index', compact('courses'));
    }

    ```

5. Store tanpa redirect

    Pada fungsi store, setelah data mata kuliah berhasil disimpan, controller langsung mengembalikan `view courses.index` tanpa melakukan redirect. Masalah ini terdapat di berkas `app/Http/Controllers/CourseController.php, pada method store`.
    Dampak Dari Sudut Pandang Pengguna (User): Setelah pengguna berhasil menambahkan mata kuliah, halaman yang ditampilkan masih merupakan hasil dari request `POST`. Jika pengguna melakukan refresh, browser dapat meminta pengiriman ulang request sehingga proses penyimpanan dapat dijalankan kembali.
    Dari Sudut Pandang Penyerang (Attacker): Penyerang dapat mengirim atau mengulangi request POST secara langsung. Jika request diproses berulang kali, hal tersebut dapat menyebabkan data diproses kembali atau membuat request yang tidak diinginkan. Redirect setelah store membantu menerapkan pola `Post/Redirect/Get`.

    Kode sebelum perbaikan : 
    ```
    public function store(Request $request)
    {
    $validated = $request->validate([
        'code'        => 'required|string|max:20|unique:courses,code',
        'name'        => 'required|string|max:255',
        'description' => 'nullable|string',
        'sks'         => 'required|integer|min:1|max:6',
        'lecturer_id' => 'required|exists:users,id',
        'status'      => 'required|in:draft,active,archived',
    ]);

    Course::create($validated);

    // SALAH: Setelah POST langsung menampilkan view
    $courses = Course::paginate(10);

    return view('courses.index', compact('courses'));
    }
    ```

    Kode setelah perbaikan : 
    ```
    public function store(Request $request)
    {
    $validated = $request->validate([
        'code'        => 'required|string|max:20|unique:courses,code',
        'name'        => 'required|string|max:255',
        'description' => 'nullable|string',
        'sks'         => 'required|integer|min:1|max:6',
        'lecturer_id' => 'required|exists:users,id',
        'status'      => 'required|in:draft,active,archived',
    ]);

    Course::create($validated);

    // BENAR: Setelah POST berhasil, lakukan redirect
    return redirect()
        ->route('courses.index')
        ->with('success', 'Mata kuliah berhasil ditambahkan.');
    }
    ```


6. Satu form tanpa @csrf
    Berada pada berkas `resources/views/courses/create.blade.php` Salah satu form yang digunakan untuk mengirim data mata kuliah melalui method `POST` tidak memiliki directive `@csrf`. Masalah ini terdapat pada form mata kuliah yang digunakan untuk mengirim data ke server.
    Dampak Dari Sudut Pandang Pengguna (User): Form yang tidak memiliki token `CSRF` dapat menyebabkan request ditolak oleh Laravel ketika perlindungan `CSRF` aktif. Akibatnya, pengguna tidak dapat melakukan proses yang seharusnya dilakukan melalui form tersebut.
    Dari Sudut Pandang Penyerang (Attacker): Tanpa perlindungan CSRF, penyerang dapat mencoba membuat halaman eksternal yang mengirim request ke aplikasi ketika korban sedang login. Request tersebut dapat dilakukan menggunakan sesi korban sehingga tindakan dapat dilakukan atas nama korban tanpa sepengetahuan korban.

    Kode sebelum perbaikan : 
    
    ```
   <form action="{{ route('courses.store') }}" method="POST" class="space-y-5">
        // Tidak menyertakan directive @csrf
    ```

    Kode setelah perbaikan : 

    ```
   <form action="{{ route('courses.store') }}" method="POST" class="space-y-5">

        @csrf 
    ```



    











