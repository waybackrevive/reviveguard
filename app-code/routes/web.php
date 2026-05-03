 <?php

use App\Http\Controllers\EvaluationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/portal/login');
});

// ── Public site evaluation form ───────────────────────────────────────────────
Route::get('/evaluate',                       [EvaluationController::class, 'show'])->name('evaluate');
Route::post('/evaluate',                      [EvaluationController::class, 'store'])->name('evaluate.submit')->middleware('throttle:5,10');
Route::get('/evaluate/thanks',                [EvaluationController::class, 'thanks'])->name('evaluate.thanks');
Route::get('/evaluate/{id}/report',           [EvaluationController::class, 'showReport'])->name('evaluate.report.show');
Route::post('/evaluate/{id}/report',          [EvaluationController::class, 'storeReport'])->name('evaluate.report.store')->middleware('throttle:10,10');

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
