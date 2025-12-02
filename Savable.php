<?php
interface Savable {
    public function save($user_id, $pickup_id, $dropoff_id, $fare = null);
}
?>