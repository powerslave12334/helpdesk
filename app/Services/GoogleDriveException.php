<?php

namespace App\Services;

use RuntimeException;

/**
 * Error controlado de Google Drive. El mensaje ya viene redactado para el
 * usuario final: se muestra tal cual en el SweetAlert del módulo de tickets.
 */
class GoogleDriveException extends RuntimeException {}
