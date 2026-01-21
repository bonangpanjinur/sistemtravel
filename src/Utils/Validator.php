<?php
// Path: src/Utils/Validator.php

namespace UmrahManagement\Utils;

class Validator {
    private $data;
    private $errors = [];

    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * Membuat instance baru (Helper static)
     */
    public static function make($data) {
        return new self($data);
    }

    /**
     * Menjalankan aturan validasi.
     * Contoh: rules(['name' => 'required', 'email' => 'required|email'])
     */
    public function rules(array $rules) {
        foreach ($rules as $field => $ruleString) {
            $ruleArray = explode('|', $ruleString);
            foreach ($ruleArray as $rule) {
                // Pisahkan rule dengan parameter (misal: min:5)
                $params = [];
                if (strpos($rule, ':') !== false) {
                    list($rule, $paramStr) = explode(':', $rule);
                    $params = explode(',', $paramStr);
                }

                $value = isset($this->data[$field]) ? $this->data[$field] : null;
                $this->applyRule($field, $rule, $value, $params);
            }
        }
        return $this;
    }

    private function applyRule($field, $rule, $value, $params) {
        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, "Field $field wajib diisi.");
                }
                break;
            case 'email':
                if (!empty($value) && !is_email($value)) {
                    $this->addError($field, "Format email tidak valid.");
                }
                break;
            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->addError($field, "Field $field harus berupa angka.");
                }
                break;
            case 'date':
                if (!empty($value) && !$this->isValidDate($value)) {
                    $this->addError($field, "Format tanggal tidak valid (YYYY-MM-DD).");
                }
                break;
            case 'min':
                if (is_string($value) && strlen($value) < $params[0]) {
                    $this->addError($field, "Minimal {$params[0]} karakter.");
                } elseif (is_numeric($value) && $value < $params[0]) {
                    $this->addError($field, "Nilai minimal adalah {$params[0]}.");
                }
                break;
        }
    }

    private function isValidDate($date) {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    private function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public function passes() {
        return empty($this->errors);
    }

    public function fails() {
        return !empty($this->errors);
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getFirstError() {
        foreach ($this->errors as $field => $messages) {
            return $messages[0];
        }
        return null;
    }
}