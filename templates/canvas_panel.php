<?php
	$critique_results = unserialize(CRITIQUE_RESULTS);
	$failures = count($critique_results[1]);
?>
<div id="canvas-panel" <?php if ($failures > 0) { echo 'class="critique-errors"'; } ?>>
	<i class="glyphicon glyphicon-menu-up" id="canvas-panel-up" onclick="expandCanvasPanel()"></i>
	<div id="canvas-panel-data">
		<h5>PHP Canvas</h5>
		<p>
			Critiques Run: <?php echo $critique_results[0]; ?><br>
			Number of Failures: <?php echo $failures;?>
		</p>
		<p>
			Failed Critiques:
		</p>
		<ol>
			<?php
				foreach ($critique_results[1] as $error) {
					if (isset($error[2]))
					{
						echo '<li>Test [ ' . $error[2] . ' ] > Class [ ' . $error[0] . ' ] > Method [ ' . $error[1] . '() ] </li>';
					}
					else
					{
						echo '<li>CRITICAL ERROR [ ' . $error[2] . ' ] Class [ ' . $error[0] . ' ]';
					}
				}
			?>
		</ol>
	</div>
</div>