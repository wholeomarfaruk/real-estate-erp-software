<?php

namespace App\Services\Client;

use App\Models\Customer;
use App\Models\User;

class ClientPayloadBuilder
{
    public static function user(User $user): array
    {
        return [
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'phone'             => $user->phone,
            'profile_photo_url' => $user->profile_photo_url,
        ];
    }

    public static function customer(?Customer $customer): ?array
    {
        if (! $customer) {
            return null;
        }

        return [
            'id'                      => $customer->id,
            'customer_id'             => $customer->customer_id,
            'type'                    => $customer->type,
            'name'                    => $customer->name,
            'father_name'             => $customer->father_name,
            'mother_name'             => $customer->mother_name,
            'date_of_birth'           => $customer->date_of_birth,
            'gender'                  => $customer->gender,
            'phone'                   => $customer->phone,
            'phone_alt'               => $customer->phone_alt,
            'email'                   => $customer->email,
            'address'                 => $customer->address,
            'district'                => $customer->district,
            'division'                => $customer->division,
            'postal_code'             => $customer->postal_code,
            'company_name'            => $customer->company_name,
            'company_registration_no' => $customer->company_registration_no,
            'company_tax_id'          => $customer->company_tax_id,
            'doc_type'                => $customer->doc_type,
            'doc_no'                  => $customer->doc_no,
            'doc_issue_date'          => $customer->doc_issue_date,
            'doc_expiry_date'         => $customer->doc_expiry_date,
            'profile_image_url'       => file_path($customer->profile_image_id),
            'doc_file_url'            => file_path($customer->doc_file_id),
            'kyc_status'              => $customer->kyc_status,
            'kyc_date'                => $customer->kyc_date,
            'status'                  => $customer->status,
            'created_at'              => $customer->created_at,
            'updated_at'              => $customer->updated_at,
        ];
    }
}
