<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Laratrust\Traits\LaratrustUserTrait;
class Affiliate extends Model
{
    use Traits\EloquentGetTableNameTrait;

    protected $appends = ['picture_saved', 'fingerprint_saved'];

    protected $fillable = [
        'user_id',
        'affiliate_state_id',
        'city_identity_card_id',
        'city_birth_id',
        'degree_id',
        'unit_id',
        'category_id',
        'pension_entity_id',
        'identity_card',
        'registration',
        'type',
        'last_name',
        'mothers_last_name',
        'first_name',
        'second_name',
        'surname_husband',
        'civil_status',
        'gender',
        'birth_date',
        'date_entry',
        'date_death',
        'reason_death',
        'date_derelict',
        'reason_derelict',
        'change_date',
        'phone_number',
        'cell_phone_number',
        'afp',
        'nua',
        'item',
        'is_duedate_undefined',
        'due_date'
      ];
 
    public function getPictureSavedAttribute()
    {
        return false;
    }

    public function getFingerprintSavedAttribute()
    {
        return true;
    }

      public function category()
      {
        return $this->belongsTo(Category::class);
      }
      public function degree()
      {
      return $this->belongsTo(Degree::class);
      }
      public function city_identity_card()
      {
        return $this->belongsTo(City::class,'city_identity_card_id', 'id');
      }
      public function affiliate_state()
      {
          return $this->belongsTo(AffiliateState::class);
      }
      public function city_birth()
      {
          return $this->belongsTo(City::class, 'city_birth_id', 'id');
      }
      public function pension_entity()
      {
          return $this->belongsTo(PensionEntity::class);
      }
      // add records 
      public function records()
      {
          return $this->morphMany(Record::class, 'recordable');
      }
      //address
      public function address()
      {
        return $this->morphToMany(Address::class, 'addressable');
      }

}