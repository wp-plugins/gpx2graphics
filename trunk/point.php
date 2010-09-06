<?php

class Gpx2GraphicsLoader_Geo_Point {
	private $_lon;
	private $_lat;
	private $_elevation;
	private $_timestamp;
	private $_timeUntilPoint;
	private $_timeFromPreviousPoint;
	private $_distanceUntilPoint;
	private $_distanceFromPreviousPoint;
	private $_speed;
	
	public function __construct($trackPoint) {
		if (isset($trackPoint)) {
			$this->_lon = trim($trackPoint['lon']);
			$this->_lat = trim($trackPoint['lat']);
			$this->_elevation = trim($trackPoint->ele);
			$this->_timestamp = strtotime(trim($trackPoint->time));
			$this->_datetime = date('Y-m-d H:i:s',strtotime(trim($trackPoint->time)));
		}
	}
	
	public function setDistanceUntilPoint($distance) {
		$this->_distanceUntilPoint = $distance;
	}
	
	public function setDistanceFromPreviousPoint($distance) {
		$this->_distanceFromPreviousPoint = $distance;
	}
	
	public function setTimeUntilPoint($distance) {
		$this->_timeUntilPoint = $distance;
	}
	
	public function setTimeFromPreviousPoint($time) {
		$this->_timeFromPreviousPoint = $time;
	}
	
	public function getDistanceUntilPoint() {
		return $this->_distanceUntilPoint;
	}
	
	public function setSpeed() {
		$distance = ($this->_distanceFromPreviousPoint*1000);
		$time = $this->_timeFromPreviousPoint;
		$seconds = $time - strtotime('1970-01-01 00:00:00');
		if ($seconds>0) {
			$this->_speed = round(($distance / $seconds)*3.6,2);
		} else {
			$this->_speed = 0;
		}
	}
	
	public function getDistanceFromPreviousPoint() {
		return $this->_distanceFromPreviousPoint;
	}

	public function getTimeFromPreviousPoint() {
		return $this->_timeFromPreviousPoint;
	}
	
	
	public function getSpeed() {
		return $this->_speed;
	}
	
	public function getLon() {
		return $this->_lon;
	}
	
	public function getLat() {
		return $this->_lat;
	}
	
	public function setLon($lon) {
		$this->_lon = $lon;
	}
	
	public function setLat($lat) {
		$this->_lat = $lat;
	}
	
	public function getTimeStamp() {
		return $this->_timestamp;
	}
	
	public function getElevation() {
		return $this->_elevation;
	}
	
}
