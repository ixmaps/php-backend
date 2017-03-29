<?php
include('../config.php');
include('../model/SLog.php');

//SLog::renderSearchLog($_REQUEST);

if(isset($_REQUEST['action']) && $_REQUEST['action']=="filter" && $_REQUEST['date1']!="" && $_REQUEST['date2']!="") 
{
	$data = SLog::renderSearchLogD3($_REQUEST, true);

} else {
	$data = SLog::renderSearchLogD3($_REQUEST, false);
}
//print_r($data);
$slogToD3 = json_encode($data['slogDataD3']);
//$slogData = json_encode($data['slogData']);

//SLog::updateGeoData();
$exploreStats = json_encode(Slog::collectExploreStats());

?>

<!DOCTYPE html>
<meta charset="utf-8">
<head>
	
	<link rel="stylesheet" href="//code.jquery.com/ui/1.11.0/themes/smoothness/jquery-ui.css">
	<script src="//code.jquery.com/jquery-1.10.2.js"></script>
	<script src="//code.jquery.com/ui/1.11.0/jquery-ui.js"></script>
	<script>

		var date1= "<?php echo $data['date1'];?>";
		var date2= "<?php echo $data['date2'];?>";

		jQuery( document ).ready(function() {
			initialize();
		});

		var initialize = function(){

			jQuery(function() {
				jQuery( "#date1" ).datepicker();
				jQuery( "#date1" ).datepicker( "option", "dateFormat", "yy-mm-dd");

			});
			
			jQuery(function() {
				jQuery( "#date2" ).datepicker();
				jQuery( "#date2" ).datepicker( "option", "dateFormat", "yy-mm-dd");
			});

			jQuery(function() {

			  jQuery("#date1").datepicker({
			    format: 'yy-mm-dd',
			    startDate: '2014-01-10',
			    endDate: ''
			  }).on("show", function() {
			    jQuery(this).val("").datepicker('update');
			  });

			});

			jQuery( "#filter-btn" ).click(function() {
				var d1= jQuery( "#date1" ).val();
				var d2= jQuery( "#date2" ).val();
				window.location.replace("sLog.php?action=filter&date1="+d1+"&date2="+d2);
			});

			jQuery( "#date1" ).val(date1);
			jQuery( "#date2" ).val(date2);

			renderSlogDetails();
			renderexploreStats();
		}

	</script>

	<style>
	.chart {
	font-family: Arial, sans-serif;
	font-size: 10px;
	}

	.axis path, .axis line {
	fill: none;
	stroke: #000;
	shape-rendering: crispEdges;
	}

	.bar {
	fill: steelblue;
	}
	</style>
</head>

<body>
<div id="slog-stats-details"></div>
<hr/>
<p>Explore Page Log: <b><?php echo $data['date1']."</b> - <b>".$data['date2']."</b>"; ?> - 

	Date From: <input type="text" id="date1" size="30" value="<?php echo $data['date1']?>"/> - 
	To: <input type="text" id="date2" size="30"value="<?php echo $data['date2']?>"/>
	<button id="filter-btn" onclick="updateFilter()">Update</button>

</p>

	<!-- <svg class="chart"></svg> -->
	<script src="http://d3js.org/d3.v3.min.js"></script>
	<script>

	var renderexploreStats = function (){
		var exploreStatsData = <?php echo $exploreStats?>;
		//console.log(exploreStatsData);
		var sLogStats = jQuery('<table border=1 class="" id="slog-stats-table"></table>');
		jQuery.each(exploreStatsData, function(sKey, sVal) {
			//console.log(slogToD3Item,value);  
			//console.log(value);  

			var sLogStatsTR = jQuery('<tr class=""><td>'+sKey+'</td><td>'+sVal+'</td></tr>');
		    jQuery(sLogStats).append(sLogStatsTR);
		    jQuery('#slog-stats-details').append(sLogStats);

		});

	}


	var data = <?php echo $slogToD3?>;

	var renderSlogDetails = function (){
		//slog-details	
		var sLogTable = jQuery('<table border=1 class="" id="slog-table"></table>');

		jQuery.each(data, function(slogToD3Item, value) {
			//console.log(slogToD3Item,value);  
			//console.log(value);  

			var sLogTableTR = jQuery('<tr class=""><td>'+value['date']+'</td><td>'+value['total']+'</td></tr>');
		    jQuery(sLogTable).append(sLogTableTR);
		    
		    jQuery('#slog-details').append(sLogTable);

		});
	}

	var margin = {top: 40, right: 40, bottom: 40, left:40},
	    width = 1000,
	    height = 400;

	var x = d3.time.scale()
	    .domain([new Date(data[0].date), d3.time.day.offset(new Date(data[data.length - 1].date), 1)])
	    .rangeRound([0, width - margin.left - margin.right]);

	var y = d3.scale.linear()
	    .domain([0, d3.max(data, function(d) { return d.total; })])
	    .range([height - margin.top - margin.bottom, 0]);

	var xAxis = d3.svg.axis()
	    .scale(x)
	    .orient('bottom')
	    .ticks(d3.time.days, 1)
	    .tickFormat(d3.time.format('%a %d'))
	    .tickSize(0)
	    .tickPadding(8);

	var yAxis = d3.svg.axis()
	    .scale(y)
	    .orient('left')
	    .tickPadding(8);

	var svg = d3.select('body').append('svg')
	    .attr('class', 'chart')
	    .attr('width', width)
	    .attr('height', height)
	  	.append('g')
	    .attr('transform', 'translate(' + margin.left + ', ' + margin.top + ')');

	svg.selectAll('.chart')
	    .data(data)
	  	.enter().append('rect')
	    .attr('class', 'bar')
	    .attr('x', function(d) { return x(new Date(d.date)); })
	    .attr('y', function(d) { return height - margin.top - margin.bottom - (height - margin.top - margin.bottom - y(d.total)) })
	    .attr('width', 10)
	    .attr('height', function(d) { return height - margin.top - margin.bottom - y(d.total) });

	svg.append('g')
	    .attr('class', 'x axis')
	    .attr('transform', 'translate(0, ' + (height - margin.top - margin.bottom) + ')')
	    .call(xAxis);

	svg.append('g')
	  .attr('class', 'y axis')
	  .call(yAxis);
	</script>

	<div id="slog-details"></div>

</body>
</html>
<?php
//print_r($data['slogDataD3']);
?>