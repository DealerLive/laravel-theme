<?php

use \DealerLive\Inventory\Helpers;

function hasProperty($vehicles, $property, $value)
{
	foreach($vehicles as $v)
	{
		if($v->$property == $value)
			return true;
	}
	return false;
}

//Variables used to filter the results
$type = (array_key_exists('type', $params)) ? $params['type'] : "all";
$showCounts = (array_key_exists('counts', $params)) ? $params['counts'] : false;
$make_count = Helpers::get_all_makes_count($type, \Request::get('classification'));
$class_count = Helpers::getClassificationCounts($type);
	
//Load options (fallback to defaults if no options saved)
$configValue = \DealerLive\Config\Helper::check('inv_filter_toggles');

if($configValue !== false)
	$configContainer = json_decode($configValue);
else
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
	$config->classification = false;
}

?>

<div id="listings-sidebar">

	@if($type == 'all' || (property_exists($config, 'classification') && $config->classification))
	<ul class="listing-navigation">
		<h4>Classification</h4>
		@if(!\Request::get('classification'))
		@foreach(Helpers::getClassifications($type) as $class)
		@if(\Request::get('q') && !hasProperty($params['vehicles'], 'classification', $class->classification))
			<?php continue; ?>
		@endif

		@if(isset($class_count[$class->classification]) && $class_count[$class->classification])
		
		<li>
			<a href="{{\URL::route('inventory', $type)}}?page=1&classification={{$class->classification}}{{\Request::get('q') ? '&q='.\Request::get('q') : null}}">
				
				{{ucwords($class->classification)}}
				@if($showCounts)
					({{(isset($class_count[$class->classification])) ? $class_count[$class->classification] : "0"}})
				@endif
				
			</a>
		</li>
		
		@endif
		@endforeach
		@else
			<a href="{{ URL::route('inventory', $type) }}?make={{\Request::get('make')}}&model={{\Request::get('model')}}{{\Request::get('q') ? '&q='.\Request::get('q') : null}}">
				<li>
					<div class="remove-filter">X</div>
					<p>{{ ucwords(\Request::get('classification')) }}
					@if($showCounts)
						({{(isset($class_count[\Request::get('classification')])) ? $class_count[\Request::get('classification')] : ""}})
					@endif
					</p>
					
				</li>
			</a>
		@endif
	</ul>
	@endif

	<ul class="listing-navigation">
		<h4>Make</h4>
		@if (!Request::get('make'))
		@foreach(Helpers::get_makes($type, \Request::get('classification')) as $v)
		@if(\Request::get('q') && !hasProperty($params['vehicles'], 'make', $v->make))
			<?php continue; ?>
		@endif
		
		
		<li>
			<a href="{{ URL::route('inventory', $type)}}?page=1&make={{$v->make}}&classification={{\Request::get('classification')}}{{\Request::get('q') ? '&q='.\Request::get('q') : null}}">
			
				{{ ucwords(strtolower($v->make)) }} 
				@if($showCounts)
					({{ (isset($make_count[$v->make])) ? $make_count[$v->make] : ''}})
				@endif
			</a>
		</li>
		
		@endforeach
		@else
			<a href="{{ URL::route('inventory', $type) }}?classification={{\Request::get('classification')}}">
				<li>
					<div class="remove-filter">X</div>
					<p>{{ Request::get('make') }}</p> 
				</li>
			</a>
		@endif
	</ul>

	@if (Request::get('make') || $type == "new")
	<ul class="listing-navigation">
		<h4>Model</h4>
		@foreach(Helpers::get_models($type, Request::get('make'), \Request::get('classification')) as $v)
		@if(\Request::get('q') && !hasProperty($params['vehicles'], 'model', $v->model))
			<?php continue; ?>
		@endif
		@if(Helpers::get_model_count($v->model, $type))
		<li>
			<a href="{{ URL::route('inventory', $type)}}?page=1&make={{Request::get('make').'&model='.$v->model }}&classification={{\Request::get('classification')}}{{\Request::get('q') ? '&q='.\Request::get('q') : null}}">
				{{ ucwords(strtolower($v->model)) }} 
				@if($showCounts)
					({{Helpers::get_model_count($v->model, $type)}})
				@endif
			</a>
		</li>
		@endif
		@endforeach
	</ul>
	@endif
	</div>