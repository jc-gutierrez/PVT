<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Voucher;
use App\LoanState;
use Illuminate\Http\Request;
use App\Http\Requests\VoucherForm;
use Util;
use Carbon;
use DB;

/** @group Tesoreria
* Datos de los registros de cobros
*/
class VoucherController extends Controller
{
    /**
    * Listado de cobros
    * Devuelve el listado con los datos paginados
    * @queryParam user_id Filtro por id de usuario. Example: 123
    * @queryParam loan_payment_id Filtro por id de préstamo. Example: 2
    * @queryParam loan_payments Filtro por tipo de cobro. Example: loan_payments
    * @queryParam search Parámetro de búsqueda. Example: TRANS000001-2020
    * @queryParam sortBy Vector de ordenamiento. Example: [created_at]
    * @queryParam sortDesc Vector de orden descendente(true) o ascendente(false). Example: [false]
    * @queryParam per_page Número de datos por página. Example: 1
    * @queryParam page Número de página. Example: 1
    * @authenticated
    * @responseFile responses/voucher/index.200.json
    */
    public function index(Request $request)
    {
        $filter = [];
        if ($request->has('user_id')) {
            $filter['user_id'] = $request->user_id;
        }
        if ($request->has('loan_payments')) {
            $filter['payable_type'] = "loan_payments";
        }
        if ($request->has('loan_payment_id')) {
            $filter['payable_id'] = $request->loan_payment_id;
            $filter['payable_type'] = "loan_payments";
        }
        return Util::search_sort(new Voucher(), $request, $filter);
    }
    /**
    * Detalle de registro de cobro
    * Devuelve el detalle de un voucher mediante su ID
    * @urlParam voucher required ID de voucher. Example: 1
    * @authenticated
    * @responseFile responses/voucher/show.200.json
    */
    public function show(Voucher $voucher)
    {
        return $voucher;
    }


    /**
    * Editar registro de cobro
    * Edita el Pago realizado.
    * @urlParam voucher required ID del registro de pago. Example: 2
    * @bodyParam payment_type_id integer required ID de tipo de pago. Example: 2
    * @bodyParam voucher_type_id integer required ID de tipo de voucher. Example: 1
    * @bodyParam voucher_number integer número de voucher. Example: 12354121
	* @bodyParam description string Texto de descripción. Example: Penalizacion regularizada
    * @authenticated
    * @responseFile responses/voucher/update_voucher.200.json
    */
    public function update(VoucherForm $request, Voucher $voucher)
    {
        DB::beginTransaction();
        try {
            $payment = $voucher;
            $payment->description = $request->input('description');
            $payment->voucher_number = $request->input('voucher_number');
            $payment->voucher_type_id = $request->voucher_type_id;
            $payment->payment_type_id = $request->payment_type_id;
            if(Util::concat_action($voucher) != 'editó'){
                Util::save_record($voucher, 'datos-de-un-pago', Util::concat_action($voucher));
                $voucher->update($payment->toArray());
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return $e;
        }
        return $payment;
    }

    /**
    * Anular registro de cobro
    * @urlParam voucher required ID del pago. Example: 1
    * @authenticated
    * @responseFile responses/voucher/destroy_voucher.200.json
    */
    public function destroy(Voucher $voucher)
    {
        DB::beginTransaction();
        try {
            $loanPayment = $voucher->payable;
            $pendienteDePago = LoanState::whereName('Pendiente de Pago')->first()->id;
            $loanPayment->update(['state_id' => $pendienteDePago]);
            $voucher->delete();
            Util::save_record($voucher, 'datos-de-un-pago', 'eliminó pago: ' . $voucher->code);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return $e;
        }
        return $voucher;
    }
}
