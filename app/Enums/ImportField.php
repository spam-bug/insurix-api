<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/** A field a spreadsheet column can fill, with the header names agents tend to use for it. */
enum ImportField: string
{
    case Name = 'name';
    case FirstName = 'first_name';
    case LastName = 'last_name';
    case Email = 'email';
    case Phone = 'phone';
    case Address = 'address';
    case Birthdate = 'birthdate';
    case CivilStatus = 'civil_status';
    case Occupation = 'occupation';
    case Interest = 'interest';
    case Notes = 'notes';
    case ProductName = 'product_name';
    case PolicyNumber = 'policy_number';
    case PolicyType = 'policy_type';
    case PolicyStatus = 'policy_status';
    case Premium = 'premium';
    case PaymentFrequency = 'payment_frequency';
    case CoverageAmount = 'coverage_amount';
    case EffectiveDate = 'effective_date';
    case RenewalDate = 'renewal_date';
    case CommissionRate = 'commission_rate';

    public function label(): string
    {
        return match ($this) {
            self::Name => 'Full name',
            self::FirstName => 'First name',
            self::LastName => 'Last name',
            self::Email => 'Email',
            self::Phone => 'Phone',
            self::Address => 'Address',
            self::Birthdate => 'Birthdate',
            self::CivilStatus => 'Civil status',
            self::Occupation => 'Occupation',
            self::Interest => 'Interested in',
            self::Notes => 'Notes',
            self::ProductName => 'Product',
            self::PolicyNumber => 'Policy number',
            self::PolicyType => 'Policy type',
            self::PolicyStatus => 'Policy status',
            self::Premium => 'Premium',
            self::PaymentFrequency => 'Payment frequency',
            self::CoverageAmount => 'Coverage amount',
            self::EffectiveDate => 'Effective date',
            self::RenewalDate => 'Renewal date',
            self::CommissionRate => 'Commission rate (%)',
        };
    }

    /** The policy fields: a client row with any of them also brings in a policy. */
    public function isPolicyField(): bool
    {
        return in_array($this, self::policyFields(), true);
    }

    /**
     * @return array<int, self>
     */
    public static function policyFields(): array
    {
        return [
            self::ProductName, self::PolicyNumber, self::PolicyType, self::PolicyStatus, self::Premium,
            self::PaymentFrequency, self::CoverageAmount, self::EffectiveDate, self::RenewalDate, self::CommissionRate,
        ];
    }

    /**
     * The fields an import of this kind can fill, in the order the matching step shows them.
     *
     * @return array<int, self>
     */
    public static function for(ImportKind $kind): array
    {
        $person = [self::Name, self::FirstName, self::LastName, self::Email, self::Phone, self::Address];

        return match ($kind) {
            ImportKind::Clients => [...$person, self::Birthdate, self::CivilStatus, self::Occupation, self::Notes, ...self::policyFields()],
            ImportKind::Leads => [...$person, self::Interest, self::Notes],
        };
    }

    /**
     * Header names that mean this field, compared without case, spaces or punctuation.
     *
     * @return array<int, string>
     */
    public function headerNames(): array
    {
        return match ($this) {
            self::Name => ['name', 'fullname', 'clientname', 'client', 'customer', 'customername', 'leadname', 'lead', 'insured', 'insuredname', 'policyholder', 'policyowner', 'contactname'],
            self::FirstName => ['firstname', 'givenname', 'fname', 'first'],
            self::LastName => ['lastname', 'surname', 'familyname', 'lname', 'last'],
            self::Email => ['email', 'emailaddress', 'mail', 'eaddress'],
            self::Phone => ['phone', 'phonenumber', 'mobile', 'mobilenumber', 'mobileno', 'cellphone', 'cellphonenumber', 'cell', 'contact', 'contactno', 'contactnumber', 'tel', 'telephone', 'number'],
            self::Address => ['address', 'homeaddress', 'location', 'city'],
            self::Birthdate => ['birthdate', 'birthday', 'dateofbirth', 'dob', 'bday'],
            self::CivilStatus => ['civilstatus', 'maritalstatus', 'civil'],
            self::Occupation => ['occupation', 'job', 'work', 'profession'],
            self::Interest => ['interest', 'interestedin', 'productinterest', 'insurancetype', 'lookingfor'],
            self::Notes => ['notes', 'note', 'remarks', 'comments', 'comment'],
            self::ProductName => ['product', 'productname', 'plan', 'planname', 'policy', 'policyname'],
            self::PolicyNumber => ['policynumber', 'policyno', 'policynum', 'policyid'],
            self::PolicyType => ['policytype', 'type', 'lineofbusiness', 'coveragetype'],
            self::PolicyStatus => ['policystatus', 'status'],
            self::Premium => ['premium', 'premiumamount', 'modalpremium', 'amount'],
            self::PaymentFrequency => ['paymentfrequency', 'frequency', 'mode', 'paymentmode', 'modeofpayment'],
            self::CoverageAmount => ['coverage', 'coverageamount', 'sumassured', 'suminsured', 'facevalue'],
            self::EffectiveDate => ['effectivedate', 'issuedate', 'issued', 'startdate', 'inceptiondate', 'datestarted'],
            self::RenewalDate => ['renewaldate', 'renewal', 'expirydate', 'expiry', 'maturitydate', 'enddate'],
            self::CommissionRate => ['commissionrate', 'commission', 'commission%'],
        };
    }

    /**
     * The lead and client details a form field can be linked to, filled in from its answer.
     *
     * @return array<int, self>
     */
    public static function formLinks(): array
    {
        return [
            self::Name, self::FirstName, self::LastName, self::Email, self::Phone, self::Address,
            self::Birthdate, self::CivilStatus, self::Occupation, self::Interest, self::Notes,
        ];
    }

    /** A linked form field always asks in this way, whatever its label says. */
    public function formFieldType(): FormFieldType
    {
        return match ($this) {
            self::Email => FormFieldType::Email,
            self::Phone => FormFieldType::Phone,
            self::Birthdate => FormFieldType::Date,
            self::CivilStatus, self::Interest => FormFieldType::Dropdown,
            self::Notes => FormFieldType::LongText,
            default => FormFieldType::ShortText,
        };
    }

    /**
     * The fixed choices of a linked dropdown, using the stored values as option ids.
     *
     * @return list<array{id: string, label: string}>
     */
    public function formOptions(): array
    {
        $cases = match ($this) {
            self::CivilStatus => CivilStatus::cases(),
            self::Interest => LeadInterest::cases(),
            default => [],
        };

        return array_map(fn (CivilStatus|LeadInterest $case) => ['id' => $case->value, 'label' => $case->label()], $cases);
    }

    /** "Mobile No." and "mobile_no" both become "mobileno". */
    public static function normalizeHeader(string $header): string
    {
        return Str::of($header)->lower()->replaceMatches('/[^a-z0-9%]/', '')->toString();
    }
}
