<?php

use \DealerLive\Inventory\Helpers;

try{
//Variables used to filter the results
$type = (array_key_exists('type', $params)) ? $params['type'] : "all";
$showCounts = (array_key_exists('counts', $params)) ? $params['counts'] : false;
$min = Helpers::get_min_price(\Request::get('make'), \Request::get('model'), $type);
$max = Helpers::get_max_price(\Request::get('make'), \Request::get('model'), $type);
$trims = \Request::has('model') ? Helpers::get_trims(\Request::get('model'), $type) : array();
$class_count = Helpers::getClassificationCounts($type);
$transmissions = \Request::has('model') ? Helpers::get_transmissions(\Request::get('model'), \Request::get('trim'), $type) : array();


//Load options (fallback to defaults if no options saved)
$configValue = \DealerLive\Config\Helper::check('inv_filter_toggles');

if($configValue !== false)
	$configContainer = json_decode($configValue);

if(!is_array($configContainer))
	$configContainer = array();

$config = null;

//Find the config that matches the current type
$searchType = ($type == 'all') ? 'new' : $type;
foreach($configContainer as $c)
	if($c->type == str_replace(' ', '_', $searchType))
		$config = $c;

if(!$config)
{
	$config = new \stdClass();
	$config->make = true;
	$config->model = true;
	$config->price = true;
	$config->trim = true;
	$config->trans = true;
	$config->year = false;
}

//Collect all the values into an array for use with
//some Helper methods, specifically the price range method
$requests = array(
	'type' => $type,
	'showCounts' => $showCounts,
	'min' => $min,
	'max' => $max,
	'make' => \Request::get('make'),
	'model' => \Request::get('model'),
	'trim' => \Request::get('trim'),
	'trans' => \Request::get('trans')
);

$years = Helpers::get_years($requests);

rsort($years);

}
catch(\Exception $ex)
{
	dd($ex->getMessage());
}
//Method generates a URL that maintains appropriate filters
function getRequest($section, $value, $value2 = null)
{	
	$segment = array();

	if(\Request::has('classification'))
		$segment[] = "classification=".urlencode(\Request::get('classification'));

	if(\Request::has('make'))
		$segment[] = "make=".urlencode(\Request::get('make'));

	if(\Request::has('model') && $section != "model")
		$segment[] = "model=".urlencode(\Request::get('model'));

	if(\Request::has('trim') && $section != "model" && $section != 'trim')
		$segment[] = "trim=".urlencode(\Request::get('trim'));

	if(\Request::has('trans') && $section != "model" && $section != 'trim' && $section != "trans")
		$segment[] = "trans=".urlencode(\Request::get('trans'));

	if($section == 'model')
	{
		if($value)
			$segment[] = "model=".urlencode($value);
	}
	elseif($section == 'price')
	{
		if($value)
			$segment[] = "minprice=".urlencode($value);

		if($value2)
			$segment[] = "maxprice=".urlencode($value2);
	}
	elseif($section == 'trim')
	{
		if($value)
			$segment[] = "trim=".urlencode($value);
	}
	elseif($section == 'trans')
	{
		if($value)
			$segment[] = "trans=".urlencode($value);
	}
	elseif($section == 'year')
	{
		if($value)
			$segment[] = "year=".urlencode($value);
	}

	$request = implode('&', $segment);
	return ($request) ? '?'.$request : '';
}

//Method determines if an option should be selected
function isSelected($object, $description = null, $value = null)
{
	if(is_null($object))
		$object = new \stdClass();

	if($description == "max" && \Request::get('maxprice') == $value)
		return true;

	if($description == "min" && \Request::get('minprice') == $value)
		return true;
	
	if($description == "trim" && \Request::get('trim') == $value)
		return true;

	if($description == "trans" && \Request::get('trans') == $value)
		return true;

	if($description == "year" && \Request::get('year') == $value)
		return true;

	if(property_exists($object, 'model'))
		return ($object->model == \Request::get('model'));

	if(property_exists($object, 'make'))
		return ($object->make == \Request::get('make'));

	return false;
}

try{
?>

<div class="listing-navigation" data-url="{{\Request::url()}}">
	<div class="listing-navigation-title">
		<h5>{{ \DealerLive\Config\Helper::check('store_name') }}</h5>
		<h3>{{trans('inventory::vehicles.'.$type.'_vehicles')}}</h3>
	</div>

	<a class="btn btn-default pull-right" data-advanced-filter>Advanced Filter</a>

	@if(property_exists($config, 'classification') && $config->classification)
	<div @if(\Request::has('afil')) style="display: none" data-hidden-filter="true" @endif class="listing-select" >
		<h5>Classification</h5>
		<select>
			<option value="">{{trans('general.choose')}} {{trans('inventory::vehicles.classification')}}</option>
			@foreach(Helpers::getClassifications($type) as $class)
			@if(!isset($class_count[$class->classification])) <?php continue; ?> @endif
			<option value="?classification={{$class->classification}}" @if($class->classification == \Request::get('classification')) selected @endif>
				{{ucwords($class->classification)}}
				{{($showCounts ? '('.$class_count[$class->classification].')' : '')}}
			</option>
			@endforeach
		</select>
	</div>
	@endif

	<div @if(\Request::has('afil')) style="display: none" @endif class="listing-select" @if(!$config->make || count(Helpers::get_makes($type, \Request::get('classification')) <= 1)) style="display: none" @endif>
		<h5>{{trans('inventory::vehicles.make')}}</h5>
		<select>
			<option value="">{{trans('general.choose')}} {{trans('inventory::vehicles.make')}}</option>
			@foreach(Helpers::get_makes($type, \Request::get('classification')) as $make)
			<option value="?make={{$make->make}}&classification={{\Request::get('classification')}}" @if(isSelected($make)) selected @endif>
				{{ $make->make }}
				{{($showCounts) ? '('.Helpers::get_make_count($make->make, $type, \Request::get('classification')).')' : ''}}
			</option>
			@endforeach
		</select> 
	</div>

	<div @if(\Request::has('afil')) style="display: none" data-hidden-filter="true" @endif class="listing-select" @if(!$config->model) style="display: none" @endif>
		<h5>{{trans('inventory::vehicles.model')}}</h5>
		<select>
			<option value="{{getRequest('model', '')}}">{{trans('general.choose')}} {{trans('inventory::vehicles.model')}}</option>
			@foreach(Helpers::get_models($type, \Request::get('make'), \Request::get('classification')) as $model)
			@if(!Helpers::get_model_count($model->model, $type, \Request::get('make'), \Request::get('classification')))
			<?php continue; ?>
			@endif
			<option value="{{getRequest('model', $model->model)}}" @if(isSelected($model)) selected @endif>
				{{ $model->model }}
				{{($showCounts) ? '('.Helpers::get_model_count($model->model, $type, \Request::get('make'), \Request::get('classification')).')' : ''}}
			</option>
			@endforeach
		</select> 
	</div>

	@if(\Request::has('model') && count($trims))

	<div  @if(\Request::has('afil')) style="display: none" @endif class="listing-select" @if(!$config->trim) style="display: none" @endif >
		<h5>{{trans('inventory::vehicles.trim')}}</h5>
		<select>
			<option value="{{getRequest('trim', '')}}">{{trans('general.choose')}} {{trans('inventory::vehicles.trim')}}</option>
			@foreach($trims as $t)
			<option value="{{getRequest('trim', $t->trim)}}" @if(isSelected(null, 'trim', $t->trim)) selected @endif>
				{{ $t->trim }}
				{{($showCounts) ? '('.Helpers::get_trim_count(\Request::get('model'), $t->trim, $type).')' : ''}}
			</option>
			@endforeach
		</select> 
	</div>

	@endif

	@if(\Request::has('model') && count($transmissions))
	<div  @if(\Request::has('afil')) style="display: none" @endif class="listing-select" @if(!$config->trans) style="display: none" @endif>
		<h5>{{trans('inventory::vehicles.transmission')}}</h5>
		<select>
			<option value="{{getRequest('trans', '')}}">{{trans('general.choose')}} {{trans('inventory::vehicles.transmission')}}</option>
			@foreach($transmissions as $trans)
			<option value="{{getRequest('trans', $trans->transmission)}}" @if(isSelected(null, 'trans', $trans->transmission)) selected @endif>
				{{ $trans->transmission }}
				{{($showCounts) ? '('.Helpers::get_trans_count($trans->transmission, \Request::get('model'), \Request::get('trim')).')' : ''}}
			</option>
			@endforeach
		</select> 
	</div>
	@endif

	<div @if(\Request::has('afil')) style="display: none" data-hidden-filter="true" @endif class="listing-select" @if(!$config->price) style="display: none" @endif>
		<h5>{{trans('inventory::vehicles.price')}}</h5>
		<select>
		<option value="{{getRequest('price', '')}}">{{trans('general.choose')}} {{trans('inventory::vehicles.price')}}</option>
		@if($min < 9999 && Helpers::vehiclesInRange(null, 9999, $requests))
			<option value="{{getRequest('price', null, '9999')}}" @if(isSelected(null, 'max', '9999')) selected @endif>
				$0 - $9,999 {{($showCounts) ? '('.Helpers::vehiclesInRange(null, 9999, $requests).')' : ''}}
			</option>
		@endif
		@if($min < 14999 && $max >= 10000 && Helpers::vehiclesInRange(10000, 14999, $requests))
			<option value="{{getRequest('price', '10000', '14999')}}" @if(isSelected(null, 'max', '14999')) selected @endif>
				$10,000 - $14,999 {{($showCounts) ? '('.Helpers::vehiclesInRange(10000, 14999, $requests).')' : ''}}
			</option>
		@endif
		@if($min < 19999 && $max >= 15000 && Helpers::vehiclesInRange(15000, 19999, $requests))
			<option value="{{getRequest('price', '15000', '19999')}}" @if(isSelected(null, 'max', '19999')) selected @endif>
				$15,000 - $19,999 {{($showCounts) ? '('.Helpers::vehiclesInRange(15000, 19999, $requests).')' : ''}}
			</option>
		@endif
		@if($min < 24999 && $max >= 20000 && Helpers::vehiclesInRange(20000, 24999, $requests))
			<option value="{{getRequest('price', '20000', '24999')}}" @if(isSelected(null, 'max', '24999')) selected @endif>
				$20,000 - $24,999 {{($showCounts) ? '('.Helpers::vehiclesInRange(20000, 24999, $requests).')' : ''}}
			</option>
		@endif
		@if($min < 29999 && $max >= 25000 && Helpers::vehiclesInRange(25000, 29999, $requests))
			<option value="{{getRequest('price', '25000', '29999')}}" @if(isSelected(null, 'max', '29999')) selected @endif>
				$25,000 - $29,999 {{($showCounts) ? '('.Helpers::vehiclesInRange(25000, 29999, $requests).')' : ''}}
			</option>
		@endif
		@if($max >= 30000 && Helpers::vehiclesInRange(30000, null, $requests))
			<option value="{{getRequest('price', '30000', null)}}" @if(isSelected(null, 'min', '30000')) selected @endif>
				$30,000+ {{($showCounts) ? '('.Helpers::vehiclesInRange(30000, null, $requests).')' : ''}}
			</option>
		@endif
		</select> 
	</div>

	
	<div @if(\Request::has('afil')) style="display: none" @endif class="listing-select" @if(!$config->year) style="display: none" @endif>
		<h5>{{trans('inventory::vehicles.year')}}</h5>
		<select>
			<option value="{{getRequest('year', '')}}">{{trans('general.choose')}} {{trans('inventory::vehicles.year')}}</option>
			@foreach($years as $y)
			<option value="{{getRequest('year', $y->year)}}" @if(isSelected(null, 'year', $y->year)) selected @endif>
				{{$y->year}}
				{{($showCounts) ? '('.Helpers::get_year_count($y->year, $requests).')' : ''}}
			</option>
			@endforeach
		</select>
	</div>
	
	@include('Theme::content.inventory_advanced_filter_horizontal')

</div>
<?php
}
catch(\Exception $ex)
{
	dd($ex->getMessage());
}
?>
