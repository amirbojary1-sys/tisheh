<?php

namespace App\Support;

use Morilog\Jalali\Jalalian;

class Format
{
    public static function number($n, int $decimals = 0): string
    {
        return strtr(number_format((float) $n, $decimals, '٫', ','), array_combine(str_split('0123456789'), ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹']));
    }

    public static function money($rial): string
    {
        return $rial === null ? 'توافقی' : self::number($rial / 10, (int) $rial % 10 === 0 ? 0 : 1).' تومان';
    }

    public static function date($date): string
    {
        return strtr(Jalalian::fromDateTime($date)->format('Y/m/d'), array_combine(str_split('0123456789'), ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹']));
    }

    public static function status($s): string
    {
        return ['awaiting_seller' => 'در انتظار تأیید فروشنده', 'confirmed' => 'تأییدشده', 'ready_to_ship' => 'آماده ارسال', 'shipped' => 'ارسال‌شده', 'delivered' => 'تحویل‌شده', 'unverified_external' => 'خارج از سامانه؛ تأییدنشده', 'open' => 'ثبت‌شده', 'reviewing' => 'در حال بررسی', 'resolved' => 'پاسخ داده‌شده', 'dismissed' => 'مختومه', 'in_review' => 'در حال بررسی آگهی', 'pending' => 'در انتظار بررسی', 'published' => 'منتشرشده', 'draft' => 'پیش‌نویس', 'rejected' => 'ردشده', 'completed' => 'تکمیل‌شده', 'cancelled' => 'لغوشده', 'inactive' => 'غیرفعال', 'accepted' => 'پذیرفته‌شده', 'active' => 'فعال', 'suspended' => 'مسدود'][$s] ?? $s;
    }

    public static function digits($s): string
    {
        if (! is_scalar($s)) {
            return '';
        }

        return strtr((string) $s, array_combine(['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'], str_split('01234567890123456789')));
    }
}
