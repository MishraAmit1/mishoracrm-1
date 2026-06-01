<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CustomFieldsForm extends Component
{
    /**
     * Create a new component instance.
     */

    protected $customFields;

    protected $customValues;
    
    public function __construct($customFields, $customValues = [])
    {
        $this->customFields = $customFields;
        $this->customValues = $customValues;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.custom-fields-form');
    }
}
