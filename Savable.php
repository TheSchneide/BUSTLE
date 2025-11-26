<?php
interface Savable {
    // Any class that uses this interface MUST have a save function
    // with these exact arguments.
    public function save($user_id, $pickup_id, $dropoff_id, $fare = null);
}
?>