<?php // Performance testing. Remove in production
$initialMemory	= 'Initial ' . formatBytes( $initialMemory ) . " bytes. ";
$peakMemory	= 'Peak ' . formatBytes( memory_get_peak_usage() ) . " bytes. ";
$endMemory	= 'End ' . formatBytes( memory_get_usage() ) . " bytes. ";
$execTime	= 'Executed in ' . round( microtime( true ) + START, 4 ) .  '. ';
echo '<p>' . $execTime . $initialMemory . $peakMemory . $endMemory .  '</p>'; ?>
</div>

<?php printJS(); ?>
<script async type='text/javascript' src='assets/m.js'></script>
</body>
</html>
