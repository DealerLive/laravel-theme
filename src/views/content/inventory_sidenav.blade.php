<?php

use \DealerLive\Inventory\Helpers;

//Variables used to filter the results
$type = (array_key_exists('type', $params)) ? $params['type'] : "all";
$showCounts = (array_key_exists('counts', $params)) ? $params['counts'] : false;
$make_count = Helpers::get_all_makes_count($type);
if(Request::get('make'))
	$model_count = Helpers::get_all_model_count(Request::get('make'), $type);

	

?>

<div id="listings-sidebar">
	<ul class="listing-navigation">
		<h4>Make</h4>
		@if (!Request::get('make'))
		@foreach(Helpers::get_makes($type) as $v)
		<a href="{{ URL::route('inventory', $type)}}?page=1&make={{$v->make}}">
			<li>
				<img src="{{ Theme::asset('img/icons/arrow.png') }}" />
				<p>
					{{ $v->make }} 
					@if($showCounts)
						({{ (isset($make_count[$v->make])) ? $make_count[$v->make] : ''}})
					@endif
				</p>
			</li>
		</a>
		@endforeach
		@else
			<a href="{{ URL::route('inventory', $type) }}">
				<li>
					<img src="{{ Theme::asset('img/icons/arrow.png') }}">
					<p>{{ Request::get('make') }}</p> 
					<div class="remove-filter">X</div>
				</li>
			</a>
		@endif
	</ul>

	@if (Request::get('make') || $type == "new")
	<ul class="listing-navigation">
		<h4>Model</h4>
		@foreach(Helpers::get_models($type, Request::get('make')) as $v)
			<a href="{{ URL::route('inventory', $type)}}?page=1&make={{Request::get('make').'&model='.$v->model }}">
				<li>
					<img src="{{ Theme::asset('img/icons/arrow.png') }}" />
					<p>
						{{ $v->model }} 
						@if($showCounts)
						({{Helpers::get_model_count($v->model, $type)}})
						@endif
					</p>
				</li>
			</a>
		@endforeach
	</ul>
	@endif
	</div>