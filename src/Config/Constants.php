<?php
// Folder: src/Config/
// File: Constants.php

namespace UmhMgmt\Config;

class Constants {
    // Status Booking
    const STATUS_PENDING   = 'pending';
    const STATUS_PAID      = 'paid';
    const STATUS_CANCELED  = 'canceled';
    const STATUS_REFUNDED  = 'refunded';

    // Status Keberangkatan
    const DEPARTURE_OPEN     = 'open';
    const DEPARTURE_CLOSED   = 'closed';
    const DEPARTURE_DEPARTED = 'departed';

    // Capabilities (Permissions)
    const CAP_MANAGE_OPTIONS = 'manage_options';
    const CAP_MANAGE_BOOKINGS = 'umh_manage_bookings';
    const CAP_VIEW_DASHBOARD  = 'umh_view_dashboard';
}