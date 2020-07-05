<?

/*
 * LEGACY
 */
if (! isset($pps))
    exit();
if ($_POST) {
    $tmp = substr($_POST['FileTemplate'], 0, - 4);
    if (! is_file(dir_root . '/tpl/' . $tmp . '.tpl'))
        copy(dir_root . '/tpl/Default.tpl', dir_root . '/tpl/' . $tmp . '.tpl');
    if (! is_file(dir_root . '/css/' . $tmp . '.css'))
        copy(dir_root . '/css/Default.css', dir_root . '/css/' . $tmp . '.css');
    if (! is_file(dir_root . '/css/' . $tmp . 'Adaptive.css'))
        copy(dir_root . '/css/DefaultAdaptive.css', dir_root . '/css/' . $tmp . 'Adaptive.css');
    if (! is_file(dir_root . '/js/' . $tmp . '.js'))
        copy(dir_root . '/js/Default.js', dir_root . '/js/' . $tmp . '.js');
}
?>