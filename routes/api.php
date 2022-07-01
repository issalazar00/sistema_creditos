<?php

use App\Http\Controllers\BoxController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\CreditProviderController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HeadquarterController;
use App\Http\Controllers\InstallmentController;
use App\Http\Controllers\MainBoxController;
use App\Http\Controllers\PrintTicketController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TypeExpenseController;
use App\Http\Controllers\UserController;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/login', [UserController::class, 'login']);

Route::get('/company-logo', function () {
	$configuration = new Company();
	$image = $configuration->select('logo')->first();
	return $image;
});

//Credit  except index
Route::get('/credits/amortization-table', [InstallmentController::class, 'printTable']);
Route::post('/credits/collect-credit/{credit}', [CreditController::class, 'collectCredit']);
Route::get('/credits/general-information/{credit}', [CreditController::class, 'generalInformation']);
Route::get('/credits/download-Receipt-PDF/{credit}', [CreditController::class, 'downloadReceiptPDF']);
Route::post('/credits/{credit}/change-status',  [CreditController::class, 'changeStatus']);
Route::get('/credits/{credit}/installments', [CreditController::class, 'installments']);
Route::resource('/credits', CreditController::class);


Route::get('/entries/show-entry/{entry}', [EntryController::class, 'showEntry']);

Route::get('/expenses/show-expense/{expense}', [ExpenseController::class, 'showExpense']);

Route::get('/installments/calculate-installments', [InstallmentController::class, 'calculateInstallments']);

Route::get('/reports/headquarters', [ReportController::class, 'ReportHeadquarters']);
Route::get('/reports/general-client', [ReportController::class, 'ReportGeneralClient']);

Route::group(['middleware' => ['auth:api']], function () {

	Route::get('/user', function (Request $request) {
		return $request->user();
	});

	//Box
	Route::resource('/boxes',  BoxController::class);

	//Main Box
	Route::post('/main-box/cash-register/{box}', [MainBoxController::class, 'cashRegister']);
	Route::get('/main-box/current-balance', [MainBoxController::class, 'currentBalance']);
	Route::resource('/main-box',  MainBoxController::class);

	//Credit
	Route::get('/credits/amortization-table', [InstallmentController::class, 'printTable']);

	//Headquarters
	Route::get('/headquarters/list-all-headquarters',  [HeadquarterController::class, 'listAllHeadquarters']);
	Route::get('/headquarters/list-headquarter',  [HeadquarterController::class, 'listHeadquarter']);
	Route::resource('/headquarters',  HeadquarterController::class);
	Route::post('/headquarters/{headquarter}/change-status',  [HeadquarterController::class, 'changeStatus']);

	//Providers
	Route::post('/credit-providers/pay-credit-provider/{credit_provider}', [CreditProviderController::class, 'payCreditProvider']);
	Route::resource('/credit-providers', CreditProviderController::class);

	//Entries
	Route::resource('/entries',  EntryController::class);

	//Expenses
	Route::resource('/expenses',  ExpenseController::class);
	Route::post('/expenses/{expense}/change-status',  [ExpenseController::class, 'changeStatus']);
	Route::resource('/type-expenses',  TypeExpenseController::class)->middleware('permission:expense.index');

	//Reports
	Route::get('/reports/credits', [ReportController::class, 'ReportCredits'])->middleware('permission:report');
	Route::get('/reports/portfolio', [ReportController::class, 'ReportPortfolio']);
	Route::get('/reports/general-credits', [ReportController::class, 'ReportGeneralCredits']);
	Route::get('/reports/headquarters-expenses', [ReportController::class, 'ReportHeadquartersExpenses']);

	//Providers
	Route::resource('/providers',  ProviderController::class);
	Route::post('/providers/{provider}/change-status',  [ProviderController::class, 'changeStatus']);
	Route::post('/providers/filter-provider-list',  [ProviderController::class, 'filterProviderList']);

	//Clients
	Route::resource('/clients',  ClientController::class);
	Route::post('/clients/{client}/change-status',  [ClientController::class, 'changeStatus']);
	Route::get('/clients/{client}/credits', [ClientController::class, 'credits']);
	Route::post('/clients/filter-client-list',  [ClientController::class, 'filterClientList']);

	//Roles
	Route::get('/roles/getAllRoles', [RoleController::class, 'getAllRoles']);
	Route::resource('/roles', RoleController::class);
	Route::get('/permissions', [RoleController::class, 'getPermissions']);

	//User
	Route::post('/users/{user}/change-status',  [UserController::class, 'changeStatus']);
	Route::resource('/users',  UserController::class);

	//Installments
	Route::resource('/installments', InstallmentController::class);
	Route::post('/installment/{credit}/pay-installment', [InstallmentController::class, 'payInstallment']);
	Route::post('/installment/reverse-payment/{id}', [InstallmentController::class, 'reversePaymentInstallment']);
	

	//Print ticket
	Route::get('/print-installment', [PrintTicketController::class, 'printInstallment']);

	//Por revisar
	Route::get('/print-installment', [PrintTicketController::class, 'printInstallment']);
	Route::get('/print-entry/{entry}', [PrintTicketController::class, 'printEntry']);

	//Configuration
	Route::resource('/configurations', CompanyController::class)->middleware('permission:configuration');
});
