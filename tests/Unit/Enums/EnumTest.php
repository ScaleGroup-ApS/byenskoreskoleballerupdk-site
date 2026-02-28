<?php

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Enums\ConversationType;
use App\Enums\OfferType;
use App\Enums\PaymentMethod;
use App\Enums\StudentStatus;
use App\Enums\UserRole;

test('UserRole has expected cases', function () {
    expect(UserRole::cases())->toHaveCount(3);
    expect(UserRole::Admin->value)->toBe('admin');
});

test('StudentStatus has expected cases', function () {
    expect(StudentStatus::cases())->toHaveCount(4);
    expect(StudentStatus::Active->value)->toBe('active');
});

test('OfferType has expected cases', function () {
    expect(OfferType::cases())->toHaveCount(2);
    expect(OfferType::Primary->value)->toBe('primary');
});

test('BookingType has expected cases', function () {
    expect(BookingType::cases())->toHaveCount(5);
    expect(BookingType::DrivingLesson->value)->toBe('driving_lesson');
});

test('BookingStatus has expected cases', function () {
    expect(BookingStatus::cases())->toHaveCount(4);
    expect(BookingStatus::Scheduled->value)->toBe('scheduled');
});

test('PaymentMethod has expected cases', function () {
    expect(PaymentMethod::cases())->toHaveCount(4);
    expect(PaymentMethod::MobilePay->value)->toBe('mobile_pay');
});

test('ConversationType has expected cases', function () {
    expect(ConversationType::cases())->toHaveCount(2);
    expect(ConversationType::Direct->value)->toBe('direct');
});
