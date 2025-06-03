<?php
/**
 * Station related functions
 */

/**
 * Get all stations
 *
 * @param bool $activeOnly If true, return only active stations (currently unused)
 * @return array Array of stations
 */
function getAllStations($activeOnly = true) {
    $sql = "SELECT * FROM Stations";
    return fetchAll($sql, []);
}

/**
 * Get station details
 *
 * @param int $stationId Station ID
 * @return array|null Station details or null if not found
 */
function getStationDetails($stationId) {
    return fetchOne("SELECT * FROM Stations WHERE station_id = ?", [$stationId]);
}

/**
 * Get all charging points for a station
 *
 * @param int $stationId Station ID
 * @return array Array of charging points
 */
function getStationChargingPoints($stationId) {
    $sql = "SELECT *, 
            CASE 
                WHEN occupied = 0 THEN 'available'
                WHEN occupied = 1 THEN 'not-available'
            END as status
            FROM Charging_Points 
            WHERE station_id = ?";
    return fetchAll($sql, [$stationId]);
}