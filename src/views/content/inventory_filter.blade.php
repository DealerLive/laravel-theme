<?php

use \DealerLive\Inventory\Helpers;

//Variables used to filter the results
$type = (array_key_exists('type', $params)) ? $params['type'] : "all";
$showCounts = (array_key_exists('counts', $params)) ? $params['counts'] : false;
$min = Helpers::get_min_price(\Request::get('make'), \Request::get('model'), $type);
$max = Helpers::get_max_price(\Request::get('make'), \Request::get('model'), $type);
$trims = \Request::has('model') ? Helpers::get_trims(\Request::get('model'), $type) : array();
$transmissions = \Request::has('model') ? Helpers::get_transmissions(\Request::get('model'), \Request::get('trim'), $type) : array();

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

//Method generates a URL that maintains appropriate filters
function getRequest($section, $value, $value2 = null)
{	
	$segment = array();
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

	if(property_exists($object, 'model'))
		return ($object->model == \Request::get('model'));

	if(property_exists($object, 'make'))
		return ($object->make == \Request::get('make'));

	return false;
}

?>

<div class="listing-navigation">
	<div class="listing-navigation-title">
		<h3>{{ucfirst($type)}} Vehicles</h3>
	</div>

	<div class="listing-select">
		<h5>Make</h5>
		<select>
			<option value="">All Vehicles</option>
			@foreach(Helpers::get_makes($type) as $make)
			<option value="?make={{$make->make}}" @if(isSelected($make)) selected @endif>
				{{ $make->make }}
				{{($showCounts) ? '('.Helpers::get_make_count($make->make, $type).')' : ''}}
			</option>
			@endforeach
		</select> 
	</div>

	<div class="listing-select">
		<h5>Model</h5>
		<select>
			<option value="{{getRequest('model', '')}}">All Models</option>
			@foreach(Helpers::get_models($type, \Request::get('make')) as $model)
			<option value="{{getRequest('model', $model->model)}}" @if(isSelected($model)) selected @endif>
				{{ $model->model }}
				{{($showCounts) ? '('.Helpers::get_model_count($model->model, $type).')' : ''}}
			</option>
			@endforeach
		</select> 
	</div>

	@if(\Request::has('model') && count($trims))

	<div class="listing-select">
		<h5>Trim</h5>
		<select>
			<option value="{{getRequest('trim', '')}}">All Trims</option>
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
	<div class="listing-select">
		<h5>Transmission</h5>
		<select>
			<option value="{{getRequest('trans', '')}}">All Transmissions</option>
			@foreach($transmissions as $trans)
			<option value="{{getRequest('trans', $trans->transmission)}}" @if(isSelected(null, 'trans', $trans->transmission)) selected @endif>
				{{ $trans->transmission }}
				{{($showCounts) ? '('.Helpers::get_trans_count($trans->transmission, \Request::get('model'), \Request::get('trim')).')' : ''}}
			</option>
			@endforeach
		</select> 
	</div>
	@endif

	<div class="listing-select">
		<h5>Price</h5>
		<select>
		<option value="{{getRequest('price', '')}}">All Prices</option>
		@if($min < 10000 && Helpers::vehiclesInRange(null, 10000, $requests))
			<option value="{{getRequest('price', null, '10000')}}" @if(isSelected(null, 'max', '10000')) selected @endif>
				Less Than $10,000 {{($showCounts) ? '('.Helpers::vehiclesInRange(null, 10000, $requests).')' : ''}}
			</option>
		@endif
		@if($min < 20000 && $max >= 10000 && Helpers::vehiclesInRange(10000, 20000, $requests))
			<option value="{{getRequest('price', '10000', '20000')}}" @if(isSelected(null, 'max', '20000')) selected @endif>
				$10,000 - $20,000 {{($showCounts) ? '('.Helpers::vehiclesInRange(10000, 20000, $requests).')' : ''}}
			</option>
		@endif
		@if($min < 30000 && $max >= 20000 && Helpers::vehiclesInRange(20000, 30000, $requests))
			<option value="{{getRequest('price', '20000', '30000')}}" @if(isSelected(null, 'max', '30000')) selected @endif>
				$20,000 - $30,000 {{($showCounts) ? '('.Helpers::vehiclesInRange(20000, 30000, $requests).')' : ''}}
			</option>
		@endif
		@if($max >= 30000 && Helpers::vehiclesInRange(30000, null, $requests))
			<option value="{{getRequest('price', '30000', null)}}" @if(isSelected(null, 'min', '30000')) selected @endif>
				$30,000+ {{($showCounts) ? '('.Helpers::vehiclesInRange(30000, null, $requests).')' : ''}}
			</option>
		@endif
		</select> 
	</div>
</div>

<script type="text/javascript">
	$(function(){
	  $('.listing-select select').change(function(){ 
	  	var url = '{{\Request::url()}}';
	    window.location = ($(this).val() !== '') ? url+$(this).val() : url;
	  });
	});
</script>