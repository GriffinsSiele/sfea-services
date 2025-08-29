<?php
namespace App\Models;

use Panoscape\History\HasHistories;

trait ObjectHistory {

    protected $customHistory = [];

	use HasHistories {
		getModelMeta as protected baseGetModelMeta;
	}

    public function clearCustomHistory() {
        $this->customHistory = [];
    }

    public function addCustomHistory($record, $checkChanges = false) {

	    if($checkChanges && isset($record['old']) && $record['new'] == $record['old'])
	        return false;

        array_push($this->customHistory, $record);

        return true;
    }

    public function getModelMeta($event)
    {
        switch($event)
        {
            case 'updating':
                $changes = $this->getDirty();

                $changed = $this->customHistory;
                foreach ($changes as $key => $value) {
                    if(static::isIgnored($this, $key)) continue;

                    array_push($changed, ['key' => $key, 'old' => $this->getOriginal($key), 'new' => $this->$key]);
                }
                return $changed;
            case 'created':
                $original = $this->getDirty();

                $created = $this->customHistory;
                foreach ($original as $key => $value) {
                    if(static::isIgnored($this, $key)) continue;

                    array_push($created, ['key' => $key, 'new' => $this->$key]);
                }
                return $created;

            case 'deleting':
            case 'restored':
                return null;
        }
    }
}