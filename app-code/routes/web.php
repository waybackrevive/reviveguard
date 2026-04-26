 <?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/portal/login');
});

Route::get('/admin-access', function () {
    return view('admin.access-code');
})->name('admin.access.form');

Route::post('/admin-access', function (Request $request) {
    $request->validate([
        'code' => ['required', 'string', 'max:100'],
    ]);

    $configuredCode = (string) env('ADMIN_ACCESS_CODE', '');
    $submittedCode = (string) $request->input('code');

    if ($configuredCode === '' || ! hash_equals($configuredCode, $submittedCode)) {
        return back()->withErrors(['code' => 'Invalid access code.'])->withInput();
    }

    $request->session()->put('admin_access_granted', true);

    return redirect('/admin');
})->middleware('throttle:10,1')->name('admin.access.submit');
