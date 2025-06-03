<?php
/**
 * General utility functions
 *
 * This file contains utility functions used throughout the application.
 */

/**
 * Format a date and time
 *
 * @param string $dateTime Date and time string
 * @param string $format Output format (default: 'Y-m-d H:i:s')
 * @return string Formatted date and time
 */

/**
 * Format currency amount
 *
 * @param float $amount Amount to format
 * @param string $currency Currency symbol (default: '€')
 * @return string Formatted currency amount
 */
function formatCurrency($amount, $currency = '€') {
    return $currency . number_format($amount, 2);
}

/**
 * Format energy amount in kWh
 *
 * @param float $amount Energy amount in kWh
 * @return string Formatted energy amount
 */
function formatEnergy($amount) {
    return number_format($amount, 2) . ' kWh';
}

