<?php

namespace TeatroMusicadoSP\Customizations\Contracts;

defined('ABSPATH') || exit;

interface Module
{
    /**
     * Registra os hooks e filtros do módulo.
     */
    public function register(): void;
}