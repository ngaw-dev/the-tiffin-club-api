<?php

return [
    'default_cutoff_time' => env('TIFFIN_DEFAULT_CUTOFF_TIME', '23:00'),
    'store_opens_time' => env('TIFFIN_STORE_OPENS_TIME', '09:00'),

    /*
     | Wellington-region suburbs delivery is available to.
     */
    'delivery_suburbs' => [
        'Te Aro',
        'Thorndon',
        'Pipitea',
        'Lambton',
        'Mount Victoria',
        'Mount Cook',
        'Brooklyn',
        'Kelburn',
        'Aro Valley',
        'Newtown',
        'Berhampore',
        'Island Bay',
        'Hataitai',
        'Kilbirnie',
        'Lyall Bay',
        'Rongotai',
        'Miramar',
        'Strathmore',
        'Karori',
        'Wadestown',
        'Ngaio',
        'Khandallah',
        'Johnsonville',
        'Broadmeadows',
        'Churton Park',
        'Tawa',
        'Lower Hutt',
        'Petone',
        'Eastbourne',
        'Porirua',
    ],
];
