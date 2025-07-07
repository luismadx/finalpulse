<?php
use FinalPulse\Training\Classes\QrCodeGenerator;

Route::get('training/qr/{texto}', QrCodeGenerator::class)
     ->where('texto', '.*');   // opcional: aceita barras, %20, etc.
