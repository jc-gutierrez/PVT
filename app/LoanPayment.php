<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\CarbonImmutable;
use Carbon;
use Illuminate\Support\Facades\DB;

class LoanPayment extends Model
{
    use Traits\EloquentGetTableNameTrait;
    //protected $primaryKey = null;
    //public $incrementing = false;
    public $timestamps = true;
    //public $guarded = ['id'];
    use SoftDeletes;
    public $fillable = [
        'loan_id',
        'estimated_date',
        'quota_number',
        'estimated_quota',
        'penal_payment',
        'accumulated_payment',
        'interest_payment',
        'capital_payment',
        'penal_remaining',
        'accumulated_remaining',
        'code',
        'procedure_modality_id',
        'state_id',
        'role_id',
        'description',
        'validated'
    ];

    function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        if (!$this->code) {
            $latest_payments = DB::table('loan_payments')->orderBy('created_at', 'desc')->limit(1)->first();
            if (!$latest_payments) $latest_payments = (object)['id' => 0];
            $this->code = implode(['PAY', str_pad($latest_payments->id + 1, 6, '0', STR_PAD_LEFT), '-', Carbon::now()->year]);
        }
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function payment_type()
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function voucher()
    {
        return $this->morphOne(Voucher::class, 'payable')->latest('updated_at');
    }

    public function modality()
    {
        return $this->belongsTo(ProcedureModality::class,'procedure_modality_id', 'id');
    }

    public static function days_interest(Loan $loan, $estimated_date = null)
    {
        $interest = [
            'penal' => 0,
            'accumulated' => 0,
            'current' => 0
        ];
        if ($loan->balance == 0) return (object)$interest;
        $estimated_date = CarbonImmutable::parse($estimated_date ?? CarbonImmutable::now()->toDateString());
        $latest_quota = $loan->payments()->first();
        if (!$latest_quota) {
            $payment_date = $loan->disbursement_date;
            $latest_quota = (object)[
                'penal_remaining' => 0,
                'accumulated_remaining' => 0
            ];
            if (!$payment_date) return (object)$interest;
        } else {
            $payment_date = Carbon::parse($latest_quota->estimated_date)->addDay()->toDateString();
        }
        $payment_date = CarbonImmutable::parse($payment_date);
        if ($estimated_date->lessThan($payment_date)) return (object)$interest;
        $diff_days = $estimated_date->diffInDays($payment_date) + 1;
        if ($estimated_date->diffInMonths($payment_date) == 0) {
            $interest['current'] = $diff_days;
            $interest['accumulated'] = 0;
        } else {
            $interest['current'] = $estimated_date->day;
        }
        if ($diff_days > $interest['current']) $interest['accumulated'] = $diff_days - $interest['current'];
        $interest['accumulated'] += $latest_quota->accumulated_remaining;
        if ($interest['accumulated'] >= 90) {
            $interest['penal'] = $interest['accumulated'];
        }
        $interest['penal'] += $latest_quota->penal_remaining;
        // Máximo 360 días para el cálculo de interés
        foreach ($interest as $key => $value) {
            if ($value > 360) $interest[$key] = 360;
        }
        return (object)$interest;
    }

    // Unión de pagos con el mismo número de cuota
    public function merge($payments)
    {
        $merged = new LoanPayment();
        foreach ($payments as $key => $payment) {
            if ($key == 0) {
                $merged = $payment;
            } else {
                $merged->penal_payment += $payment->penal_payment;
                $merged->accumulated_payment += $payment->accumulated_payment;
                $merged->capital_payment += $payment->capital_payment;
                $merged->interest_payment += $payment->interest_payment;
            }
            if (!next($payments)) {
                $merged->pay_date = $payment->pay_date;
                $merged->estimated_date = $payment->estimated_date;
                $merged->penal_remaining = $payment->penal_remaining;
                $merged->accumulated_remaining = $payment->accumulated_remaining;
            }
        }
        unset($merged->affiliate_id, $merged->payment_type, $merged->voucher_number, $merged->receipt_number, $merged->description, $merged->created_at, $merged->updated_at);
        return $merged;
    }

    public static function quota_date(Loan $loan, $first = false)
    {
        $quota = 1;
        $latest_quota = $loan->last_payment;
        $estimated_date = Carbon::now()->endOfMonth();
        if (!$latest_quota || $first) {
            $payment_date = $loan->disbursement_date ? $loan->disbursement_date : $loan->request_date;
            $payment_date = CarbonImmutable::parse($payment_date);
            if ($estimated_date->lessThan($payment_date) || $first) $estimated_date = $payment_date->endOfMonth();
            if ($payment_date->day >= LoanGlobalParameter::latest()->first()->offset_interest_day && $estimated_date->diffInMonths($payment_date) == 0) {
                $estimated_date = $payment_date->startOfMonth()->addMonth()->endOfMonth();
            }
        } else {
            $estimated_date = Carbon::parse($latest_quota->estimated_date)->startOfMonth()->addMonth()->endOfMonth();
            $quota = $latest_quota->quota_number + 1;
        }
        return (object)[
            'date' => $estimated_date->toDateString(),
            'quota' => $quota
        ];
    }

    public function state()
    {
      return $this->belongsTo(LoanState::class, 'state_id','id');
    }

    public function records()
    {
        return $this->morphMany(Record::class, 'recordable')->latest('updated_at');
    }
}
