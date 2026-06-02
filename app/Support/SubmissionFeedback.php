<?php

namespace App\Support;

class SubmissionFeedback
{
    public static function successTitle(): string
    {
        return 'Submission successful.';
    }

    public static function bookingSuccessBody(): string
    {
        return 'Your appointment request has been received.';
    }

    public static function failureTitle(): string
    {
        return 'Submission failed.';
    }

    public static function failureIntro(): string
    {
        return 'Please review the following issues.';
    }

    public static function fallbackFailureBody(): string
    {
        return 'Please review the submitted form and try again.';
    }

    /**
     * @param  array<string, array<int, mixed>|mixed>  $errors
     * @return list<string>
     */
    public static function validationMessages(array $errors, ?int $limit = null): array
    {
        $messages = collect($errors)
            ->flatten()
            ->filter(static fn (mixed $message): bool => filled($message))
            ->map(static fn (mixed $message): string => trim((string) $message))
            ->filter()
            ->unique()
            ->values();

        if ($limit !== null) {
            $messages = $messages->take($limit)->values();
        }

        return $messages->all();
    }

    /**
     * @param  array<string, array<int, mixed>|mixed>  $errors
     */
    public static function validationFailureBody(array $errors, int $limit = 3): string
    {
        $messages = self::validationMessages($errors, $limit);

        if ($messages === []) {
            return self::fallbackFailureBody();
        }

        return collect($messages)
            ->map(static fn (string $message): string => '<p>'.e($message).'</p>')
            ->implode('');
    }
}
