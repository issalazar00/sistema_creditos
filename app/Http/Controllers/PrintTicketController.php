<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Credit;
use App\Models\Entry;
use App\Models\Expense;
use App\Models\Headquarter;
use Illuminate\Http\Request;
use Exception;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

class PrintTicketController extends Controller
{

	public function printEntry(Request $request, Entry $entry)
	{

		$company = Company::first();
		// Orden
		$headquarter = Headquarter::findOrFail($entry->headquarter_id)->first();

		if (!$headquarter->pos_printer || $headquarter->pos_printer == '') {
			return false;
		}

		$credit = $entry->credit_id ? Credit::findOrFail($entry->credit_id) : NULL;
		$client = $entry->credit_id ?  $credit->client()->first() : NULL;

		// Config de impresora
		$connector = new WindowsPrintConnector($headquarter->pos_printer);

		try {

			$printer = new Printer($connector);
			$printer->initialize();
			$printer->setJustification(Printer::JUSTIFY_CENTER);
			try {
				$logo = EscposImage::load('logo.jpg');
				$printer->bitImage($logo);
			} catch (Exception $e) {
				/* Images not supported on your PHP, or image file not found */
				//$printer->text($e->getMessage() . "\n");
			}

			$printer->setTextSize(1, 2);
			$printer->setEmphasis(true);
			$printer->text($company->name . "\n");
			$printer->setEmphasis(true);
			$printer->text('Sede ' . $headquarter->headquarter . "\n");
			$printer->setTextSize(1, 1);
			$printer->setEmphasis(false);
			$printer->text("NIT: ");
			$printer->text($headquarter->nit . "\n");
			$printer->text("Dirección: ");
			$printer->text($headquarter->address . "\n");
			$printer->text("Contacto: ");
			$printer->text($headquarter->telephone ?? $headquarter->telephone .  $headquarter->mobile ?? $headquarter->mobile ."\n");
			$printer->text("\n");
			$printer->setEmphasis(true);
			$printer->text($entry->type_entry);
			$printer->text("\n");
			$printer->setJustification(Printer::JUSTIFY_LEFT);
			$printer->setEmphasis(false);
			$printer->text('No. Ingreso: ' . $entry->id);
			$printer->text("\n");
			if ($credit) {
				$printer->text('Cliente: ' . $client->name . ' ' . $client->last_name);
				$printer->text("\n");
				$printer->text('Fecha: ' . date('Y-m-d h:i:s A'));
				$printer->text("\n");
				$printer->text(sprintf('%-20s %-20s', 'Valor Crédito: ', ' $ ' . number_format($credit->credit_value, 0, '', '.') ));
				$printer->text("\n");
			}
			$printer->text(sprintf('%-20s %-20s', 'Monto Cancelado: ', '$ ' . number_format($entry->price, 0, '', '.')));

			$printer->text(sprintf('%-20s %-20s', 'Saldo pendiente: ', '$ ' . number_format($credit->credit_to_pay - $credit->credit_paid, 0, '', '.')));

			$printer->text("\n");
			if ($credit) {
				$printer->text('Motivo de crédito: ' . $credit->description);
				$printer->text("\n");
			}
			$printer->setEmphasis(true);
			$printer->text("Descripcion: \n");
			$printer->setEmphasis(false);
			$printer->text($entry->description);
			$printer->text("\n");
			if ($credit) {
				$printer->setTextSize(1, 2);
				$printer->setEmphasis(true);
				$printer->text(sprintf('%-20s %-20s', 'Cupo crédito', ' $ ' . $client->maximum_credit_allowed));
				$printer->setTextSize(1, 1);
			}
			$printer->text("\n");
			$printer->setLineSpacing(2);
			$printer->setEmphasis(false);
			$printer->setJustification(Printer::JUSTIFY_CENTER);

			$printer->setFont(Printer::MODE_FONT_B);
			$printer->text("Tecnoplus");
			$printer->text("\nwww.tecnoplus.com\n");
			$printer->cut();
			$printer->pulse();
			$printer->close();

			return redirect()->back()->with("mensaje", "Ticket impreso");
		} catch (\Throwable $th) {
			echo $th;
			return 'No se pudo imprimir';
		}
	}

	public function printExpense(Request $request, Expense $expense)
	{

		$company = Company::first();
		// Orden
		$headquarter = Headquarter::findOrFail($expense->headquarter_id)->first();

		if (!$headquarter->pos_printer || $headquarter->pos_printer == '') {
			return false;
		}
		$user = $expense->user()->first();
		// exit;

		// Config de impresora
		$connector = new WindowsPrintConnector($headquarter->pos_printer);

		try {

			$printer = new Printer($connector);
			$printer->initialize();
			$printer->setJustification(Printer::JUSTIFY_CENTER);
			try {
				$logo = EscposImage::load('logo.jpg');
				$printer->bitImage($logo);
			} catch (Exception $e) {
				/* Images not supported on your PHP, or image file not found */
				//$printer->text($e->getMessage() . "\n");
			}

			$printer->setTextSize(1, 2);
			$printer->setEmphasis(true);
			$printer->text($company->name . "\n");
			$printer->setEmphasis(true);
			$printer->text('Sede ' . $headquarter->headquarter . "\n");
			$printer->setTextSize(1, 1);
			$printer->setEmphasis(false);
			$printer->text("NIT: ");
			$printer->text($headquarter->nit . "\n");
			$printer->text("Dirección: ");
			$printer->text($headquarter->address . "\n");
			$printer->text("Contacto: ");
			$printer->text($headquarter->telephone ?? $headquarter->telephone .  $headquarter->mobile ?? $headquarter->mobile ."\n");
			$printer->text("\n");
			$printer->setEmphasis(true);
			$printer->setJustification(Printer::JUSTIFY_LEFT);
			$printer->setEmphasis(false);
			$printer->text(sprintf('%-18s %-22s', 'Usuario responsable:', $user->name . ' ' . $user->last_name));
			$printer->text("\n");
			$printer->text(sprintf('%-18s %-22s', 'Fecha:', date('Y-m-d h:i:s A')));
			$printer->text("\n");
			$printer->text("\n");
			$printer->text(sprintf('%-20s %-20s', 'ID egreso: ', '# ' .  $expense->id));
			$printer->text("\n");
			$printer->text(sprintf('%-20s %-20s', 'Tipo de movimiento: ',  $expense->type_movement));
			$printer->text("\n");
			$printer->text(sprintf('%-20s %-20s', 'Tipo de egreso: ',  $expense->type_output));
			$printer->text("\n");
			$printer->text(sprintf('%-20s %-20s', 'Monto Cancelado: ', '$ ' .  $expense->price));
			$printer->text("\n");
			$printer->text('Descripcion: ' . $expense->description);
			$printer->text("\n");
			$printer->setTextSize(1, 2);
			$printer->setEmphasis(true);
			$printer->setTextSize(1, 1);
			$printer->text("\n");
			$printer->setLineSpacing(2);
			$printer->setEmphasis(false);
			$printer->setJustification(Printer::JUSTIFY_CENTER);

			$printer->setFont(Printer::MODE_FONT_B);
			$printer->text("Tecnoplus");
			$printer->text("\nwww.tecnoplus.com\n");
			$printer->cut();
			$printer->pulse();
			$printer->close();

			return redirect()->back()->with("mensaje", "Ticket impreso");
		} catch (\Throwable $th) {
			echo $th;
			return 'No se pudo imprimir';
		}
	}
}
