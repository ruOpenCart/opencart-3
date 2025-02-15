<?php
namespace Squareup;
class Exception extends \Exception {
    const ERR_CODE_ACCESS_TOKEN_REVOKED = 'ACCESS_TOKEN_REVOKED';
    const ERR_CODE_ACCESS_TOKEN_EXPIRED = 'ACCESS_TOKEN_EXPIRED';
    private object $config;
    private object $log;
    private object $language;
    private object $errors;
    private bool $isCurlError = false;
    private $overrideFields = [
        'billing_address.country',
        'shipping_address.country',
        'email_address',
        'phone_number'
    ];

    public function __construct($registry, $errors, $is_curl_error = false) {
        $this->errors = $errors;
        $this->isCurlError = $is_curl_error;
        $this->config = $registry->get('config');
        $this->log = $registry->get('log');
        $this->language = $registry->get('language');

        $message = $this->concatErrors();

        if ($this->config->get('config_error_log')) {
            $this->log->write($message);
        }

        parent::__construct($message);
    }

    public function isCurlError(): bool {
        return $this->isCurlError;
    }

    public function isAccessTokenRevoked(): bool {
        return $this->errorCodeExists(self::ERR_CODE_ACCESS_TOKEN_REVOKED);
    }

    public function isAccessTokenExpired(): bool {
        return $this->errorCodeExists(self::ERR_CODE_ACCESS_TOKEN_EXPIRED);
    }

    protected function errorCodeExists(string $code): bool {
        if (!empty($this->errors) && is_array($this->errors)) {
            foreach ($this->errors as $error) {
                if ($error['code'] == $code) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function overrideError(string $field): string {
        return $this->language->get('squareup_override_error_' . $field);
    }

    protected function parseError(array $error): string {
        if (!empty($error['field']) && in_array($error['field'], $this->overrideFields)) {
            return $this->overrideError($error['field']);
        }

        $message = $error['detail'];

        if (!empty($error['field'])) {
            $message .= sprintf($this->language->get('squareup_error_field'), $error['field']);
        }

        return $message;
    }

    protected function concatErrors(): string {
        $messages = [];

        if (is_array($this->errors)) {
            foreach ($this->errors as $error) {
                $messages[] = $this->parseError($error);
            }
        } else {
            $messages[] = $this->errors;
        }

        return implode(' ', $messages);
    }
}