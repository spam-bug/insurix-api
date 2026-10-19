<?php

namespace App\Enums;

enum DocumentCategory: string
{
    case PolicyContract = 'policy_contract';
    case ApplicationForm = 'application_form';
    case ValidId = 'valid_id';
    case MedicalRecord = 'medical_record';
    case Receipt = 'receipt';
    case Requirement = 'requirement';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PolicyContract => 'Policy contract',
            self::ApplicationForm => 'Application form',
            self::ValidId => 'Valid ID',
            self::MedicalRecord => 'Medical record',
            self::Receipt => 'Payment receipt',
            self::Requirement => 'Other requirement',
            self::Other => 'Other',
        };
    }
}
