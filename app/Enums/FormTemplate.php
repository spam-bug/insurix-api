<?php

declare(strict_types=1);

namespace App\Enums;

use App\Support\FormTheme;

/** Ready-made forms to start from. Picking one copies its document into a new, editable form. Most link their fields to leads; Feedback doesn't. */
enum FormTemplate: string
{
    case Contact = 'contact';
    case QuoteRequest = 'quote_request';
    case PolicyReview = 'policy_review';
    case ClientDetails = 'client_details';
    case Referral = 'referral';
    case Feedback = 'feedback';

    public function label(): string
    {
        return match ($this) {
            self::Contact => 'Contact form',
            self::QuoteRequest => 'Free quote request',
            self::PolicyReview => 'Policy review',
            self::ClientDetails => 'Client details update',
            self::Referral => 'Referral',
            self::Feedback => 'Feedback survey',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Contact => 'Name, contact details, what they’re interested in and a message.',
            self::QuoteRequest => 'What cover they need, for whom, and their budget.',
            self::PolicyReview => 'For people who already have a policy and want it checked.',
            self::ClientDetails => 'Clients send their birthdate, civil status, occupation and address.',
            self::Referral => 'A client recommends a friend or family member.',
            self::Feedback => 'Ask clients how you’re doing. Not linked to leads.',
        };
    }

    /**
     * The template's sections and settings, in the shape the builder edits.
     *
     * @return array{sections: list<array<string, mixed>>, settings: array<string, mixed>, theme: array<string, mixed>}
     */
    public function document(): array
    {
        return match ($this) {
            self::Contact => self::form('Get in touch', 'Leave your details and I’ll get back to you.', [
                self::section('contact', null, null, [
                    self::linked(ImportField::Name, required: true),
                    self::linked(ImportField::Email),
                    self::linked(ImportField::Phone),
                    self::linked(ImportField::Interest, 'What are you interested in?'),
                    self::linked(ImportField::Notes, 'Message'),
                ]),
            ]),
            self::QuoteRequest => self::form('Get a free quote', 'Tell me what you need and I’ll prepare options for you.', [
                self::section('you', 'About you', null, [
                    self::linked(ImportField::Name, required: true),
                    self::linked(ImportField::Email),
                    self::linked(ImportField::Phone),
                ]),
                self::section('cover', 'What you need', null, [
                    self::linked(ImportField::Interest, 'Type of insurance', required: true),
                    self::choice('who', FormFieldType::SingleChoice, 'Who is it for?', ['Myself', 'My family', 'My business']),
                    self::custom('budget', FormFieldType::ShortText, 'Monthly budget', 'For example ₱2,000'),
                    self::linked(ImportField::Notes, 'Anything else I should know?'),
                ]),
            ]),
            self::PolicyReview => self::form('Free policy review', 'Already insured? I’ll check that your cover still fits.', [
                self::section('you', 'About you', null, [
                    self::linked(ImportField::Name, required: true),
                    self::linked(ImportField::Email),
                    self::linked(ImportField::Phone),
                ]),
                self::section('policy', 'Your current policy', null, [
                    self::custom('insurer', FormFieldType::ShortText, 'Insurance company'),
                    self::linked(ImportField::Interest, 'Type of policy'),
                    self::custom('renewal', FormFieldType::Date, 'When does it renew?'),
                    self::linked(ImportField::Notes, 'What would you like reviewed?'),
                ]),
            ]),
            self::ClientDetails => self::form('Update your details', 'Keep your records up to date.', [
                self::section('details', null, null, [
                    self::linked(ImportField::Name, required: true),
                    self::linked(ImportField::Email),
                    self::linked(ImportField::Phone),
                    self::linked(ImportField::Birthdate),
                    self::linked(ImportField::CivilStatus),
                    self::linked(ImportField::Occupation),
                    self::linked(ImportField::Address),
                ]),
            ]),
            self::Referral => self::form('Refer someone', 'Know someone who needs cover? Share their details.', [
                self::section('referrer', 'You', null, [
                    self::custom('referrer_name', FormFieldType::ShortText, 'Your name', required: true),
                ]),
                self::section('friend', 'The person you’re referring', null, [
                    self::linked(ImportField::Name, 'Their name', required: true),
                    self::linked(ImportField::Email, 'Their email'),
                    self::linked(ImportField::Phone, 'Their phone'),
                    self::linked(ImportField::Interest, 'What might they need?'),
                    self::custom('consent', FormFieldType::Consent, 'They agreed that I share their details', required: true),
                ]),
            ]),
            self::Feedback => self::form('How did I do?', 'Your feedback helps me serve you better.', [
                self::section('feedback', null, null, [
                    self::choice('rating', FormFieldType::SingleChoice, 'How happy are you with my service?', ['Very happy', 'Happy', 'Okay', 'Unhappy']),
                    self::custom('best', FormFieldType::LongText, 'What did I do well?'),
                    self::custom('better', FormFieldType::LongText, 'What could I do better?'),
                    self::choice('recommend', FormFieldType::YesNo, 'Would you recommend me to a friend?', []),
                ]),
            ], addToLeads: false),
        };
    }

    /**
     * A new form without a template: one empty section, not linked to anything.
     *
     * @return array{sections: list<array<string, mixed>>, settings: array<string, mixed>, theme: array<string, mixed>}
     */
    public static function blankDocument(string $title): array
    {
        return self::form($title, null, [self::section('main', null, null, [])], addToLeads: false);
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return array{sections: list<array<string, mixed>>, settings: array<string, mixed>, theme: array<string, mixed>}
     */
    private static function form(string $title, ?string $description, array $sections, bool $addToLeads = true): array
    {
        return [
            'sections' => $sections,
            'settings' => [
                'title' => $title,
                'description' => $description,
                'submit_label' => 'Send',
                'success_message' => 'Thank you! I’ll be in touch soon.',
                'auto_add_leads' => $addToLeads,
            ],
            'theme' => FormTheme::defaults(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    private static function section(string $id, ?string $title, ?string $description, array $fields): array
    {
        return ['id' => $id, 'title' => $title, 'description' => $description, 'fields' => $fields];
    }

    /**
     * @return array<string, mixed>
     */
    private static function linked(ImportField $link, ?string $label = null, bool $required = false): array
    {
        return self::field($link->value, $link->formFieldType(), $label ?? $link->label(), null, $required, $link->formOptions(), $link);
    }

    /**
     * @return array<string, mixed>
     */
    private static function custom(string $id, FormFieldType $type, string $label, ?string $placeholder = null, bool $required = false): array
    {
        return self::field($id, $type, $label, $placeholder, $required, [], null);
    }

    /**
     * @param  list<string>  $options
     * @return array<string, mixed>
     */
    private static function choice(string $id, FormFieldType $type, string $label, array $options): array
    {
        return self::field($id, $type, $label, null, false, array_map(fn (string $option) => ['id' => str($option)->slug('_')->toString(), 'label' => $option], $options), null);
    }

    /**
     * @param  list<array{id: string, label: string}>  $options
     * @return array<string, mixed>
     */
    private static function field(string $id, FormFieldType $type, string $label, ?string $placeholder, bool $required, array $options, ?ImportField $link): array
    {
        return [
            'id' => $id,
            'type' => $type->value,
            'label' => $label,
            'help' => null,
            'placeholder' => $placeholder,
            'required' => $required,
            'options' => $options,
            'link' => $link?->value,
        ];
    }
}
