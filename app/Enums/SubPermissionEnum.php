<?php

namespace App\Enums;

enum SubPermissionEnum: int
{
    // User
    case user_information = 1;
    case user_password = 2;
    case user_permission = 3;
    public const USERS = [
        1 => "account_information",
        2 => "update_account_password",
        3 => "permissions"
    ];
        // settings
    case setting_language = 21;
    case setting_job = 22;
    case setting_destination = 23;
    case setting_checklist = 24;
    case setting_news_type = 25;
    case setting_priority = 26;
    public const SETTINGS = [
        21 => "language",
        22 => "job",
        23 => "destination",
        24 => "checklist",
        25 => "news_type",
        26 => "priority",
    ];
        // Approval
    case pending_approval = 31;
    case approved_approval = 32;
    case rejected_approval = 33;
    public const APPROVALS = [
        31 => "user",
        32 => "ngo",
        33 => "donor"
    ];
        // NGO
    case ngo_information = 52;
    case ngo_director_information = 53;
    case ngo_agreement = 54;
    case ngo_more_information = 55;
    case ngo_status = 56;
    case ngo_representative = 57;
    case ngo_update_account_password = 58;
    public const NGO = [
        52 => "account_information",
        53 => "director_information",
        54 => "agreement_checklist",
        55 => "more_information",
        56 => "status",
        57 => "representative",
        58 => "update_account_password",
    ];
        // ABOUT
    case about_director = 71;
    case about_manager = 72;
    case about_office = 73;
    case about_slider = 74;
    case about_technical = 75;
    public const ABOUT = [
        71 => "director",
        72 => "manager",
        73 => "office",
        74 => "pic",
        75 => "technical_sup"
    ];
}
