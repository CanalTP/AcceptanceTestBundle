<?php

namespace CanalTP\AcceptanceTestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * IndexController for AcceptanceTest
 * User interface for visualizing tests in behat format
 *
 * @author Thomas Noury <thomas.noury@canaltp.fr>
 * @copyright (c) 2015 Canal TP
 */
class IndexController extends Controller
{
    /**
     * Index action
     *
     * @return Response
     */
    public function indexAction()
    {
        $scanDir = $this->container->getParameter('at.ui_scan_dir');
        $bundles = $this->browseBundles($scanDir);

        return $this->render('CanalTPAcceptanceTestBundle:Index:index.html.twig', array('bundles' => $bundles));
    }

    /**
     * Returns complete list of bundles with features and scenarios
     *
     * @param string $dir
     * @return array
     */
    private function browseBundles($dir)
    {
        $bundleList = array();
        if (is_dir($dir)) {
            $dh = opendir($dir);
            if ($dh) {
                while (($file = readdir($dh)) !== false) {
                    if ($file !== '.' && $file !== '..') {
                        $hasFeatures = $this->hasFeatures($dir.'/'.$file);
                        if ($hasFeatures) {
                            $bundleList[] = array(
                                'title' => $file,
                                'features' => $this->browseFeatures($dir.'/'.$file),
                            );
                        }
                    }
                }
                closedir($dh);
            }
        }

        return $bundleList;
    }

    private function recurseBrowse($dir)
    {
        $featureList = array();
        if (is_dir($dir)) {
            $dh = opendir($dir);
            if ($dh) {
                while (($file = readdir($dh)) !== false) {
                    $scenarios = '';
                    if ($file != '.' && $file != '..') {
                        if (filetype($dir.'/'.$file) === 'dir') {
                            $featureList = array_merge($featureList, $this->recurseBrowse($dir.'/'.$file));
                        } else {
                            $extData = explode('.', $file);
                            if ($extData[1] == 'feature') {
                                $scenarios = $this->getFeatureText($dir.'/'.$file);
                            }
                            $featureList[] = array(
                                'title' => $dir.'/'.$file,
                                'scenarios' => $scenarios,
                            );
                        }
                    }
                }
                closedir($dh);
            }
        }

        return $featureList;
    }

    /**
     * Tells if the specified bundle dir has features
     *
     * @param string $dir
     * @return boolean
     */
    private function hasFeatures($dir)
    {
        return is_dir($dir.'/Features/Scenarios');
    }

    private function browseFeatures($dir)
    {
        $featureDir = $dir.'/Features/Scenarios';

        return $this->recurseBrowse($featureDir);
    }

    /**
     * Retrieves and parses the feature file
     *
     * @param string $file
     * @return string
     */
    private function getFeatureText($file)
    {
        $text = file_get_contents($file)."\n";
        $text = preg_replace("#Feature:(.+)\n#sU", "<strong class=\"featlabel\">Feature:$1</strong>\n", $text);
        $text = preg_replace("#Background:(.+)\n#sU", "<strong>Background:</strong>$1\n", $text);
        $text = preg_replace("#Scenario:(.+)\n#sU", "<strong>Scenario:</strong><strong>$1</strong>\n", $text);
        $text = preg_replace("#Given(.+)\n#sU", "<em>Given</em>$1\n", $text);
        $text = preg_replace("#And(.+)\n#sU", "<em>And</em>$1\n", $text);
        $text = preg_replace("#When(.+)\n#sU", "<em>When</em>$1\n", $text);
        $text = preg_replace("#Then(.+)\n#sU", "<em>Then</em>$1\n", $text);

        return trim($text);
    }
}
