<?php

declare(strict_types=1);

namespace App\Enums;

enum HealthStatus: string
{
    case EXCELLENT = 'EXCELLENT';
    case STABLE    = 'STABLE';
    case WARNING   = 'WARNING';
    case CRITICAL  = 'CRITICAL';

    /** Map a 0-100 score to a status using config('nexus.status_thresholds'). */
    public static function fromScore(float $score): self
    {
        /** @var array{excellent:int|float, stable:int|float, warning:int|float} $t */
        $t = config('nexus.status_thresholds');

        return match (true) {
            $score >= $t['excellent'] => self::EXCELLENT,
            $score >= $t['stable']    => self::STABLE,
            $score >= $t['warning']   => self::WARNING,
            default                   => self::CRITICAL,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::EXCELLENT => 'Sangat sehat',
            self::STABLE    => 'Stabil',
            self::WARNING   => 'Waspada',
            self::CRITICAL  => 'Kritis',
        };
    }

    public function summary(): string
    {
        return match ($this) {
            self::EXCELLENT => 'Kuat di semua aspek. Pertahankan yang sudah berjalan baik.',
            self::STABLE    => 'Secara umum sehat, ada beberapa area yang perlu diperkuat.',
            self::WARNING   => 'Ada titik tekanan nyata. Tindak lanjuti temuan utama dalam waktu dekat.',
            self::CRITICAL  => 'Perlu penanganan segera agar tidak terjadi krisis kas atau solvabilitas.',
        };
    }

    /** Full class strings (kept literal so Tailwind's scanner can see them). */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::EXCELLENT => 'bg-teal-100 text-teal-800 ring-teal-600/20',
            self::STABLE    => 'bg-sky-100 text-sky-800 ring-sky-600/20',
            self::WARNING   => 'bg-amber-100 text-amber-800 ring-amber-600/20',
            self::CRITICAL  => 'bg-rose-100 text-rose-800 ring-rose-600/20',
        };
    }

    public function barClass(): string
    {
        return match ($this) {
            self::EXCELLENT => 'bg-teal-600',
            self::STABLE    => 'bg-sky-600',
            self::WARNING   => 'bg-amber-500',
            self::CRITICAL  => 'bg-rose-600',
        };
    }

    public function hex(): string
    {
        return match ($this) {
            self::EXCELLENT => '#0f766e',
            self::STABLE    => '#0369a1',
            self::WARNING   => '#b45309',
            self::CRITICAL  => '#be123c',
        };
    }
}
