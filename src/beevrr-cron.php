<?php
/*
 * beevrr-cron
 * github.com/01mu
 */

class BeevrCron
{
    private $conn;

    public function conn($server, $user, $pw, $db)
    {
        try
        {
            $conn = new PDO("mysql:host=$server;dbname=$db", $user, $pw);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch(PDOException $e)
        {
            echo "Error: " . $e->getMessage();
        }

        $this->conn = $conn;
    }

    public function update()
    {
        try
        {
            $sql = 'SELECT * FROM discussions';
            $stmt = $this->conn->query($sql);
            $results = $stmt->fetchAll();
        }
        catch(PDOException $e)
        {
            echo "Error: " . $e->getMessage();
        }

        foreach($results as $result)
        {
            $to_finished = false;

            $id = $result['id'];
            $current_phase = $result['current_phase'];

            switch($current_phase)
            {
                case 'pre-argument':
                    $next = $result['pa_end_date'];
                    $ph = 'argument';
                    break;
                case 'argument':
                    $next = $result['a_end_date'];
                    $ph = 'post-argument';
                    break;
                case 'post-argument':
                    $next = $result['v_end_date'];
                    $ph = 'finished';
                    $to_finished = true;
                    break;
                default:
                    continue;
                    break;
            }

            if(time() >= $next)
            {
                try
                {
                    $sql = 'UPDATE discussions SET current_phase = ?
                        WHERE id = ?';
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute([$ph, $id]);
                }
                catch(PDOException $e)
                {
                    echo "Error: " . $e->getMessage();
                }

                $str = 'proposition "' . $id . '" changed to ' . '"' .
                    $ph . '"';

                $this->update_log($str);
            }

            if($to_finished)
            {
                $for_change = $result['for_change'];
                $aga_change = $result['against_change'];

                if($for_change > $aga_change)
                {
                    $winner = 'for';
                }
                else if($for_change < $aga_change)
                {
                    $winner = 'against';
                }
                else
                {
                    $winner = 'draw';
                }

                try
                {
                    $sql = 'UPDATE discussions SET winner = ? WHERE id = ?';
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute([$winner, $id]);
                }
                catch(PDOException $e)
                {
                    echo "Error: " . $e->getMessage();
                }

                $str = 'winner set for proposition "' . $id .
                    '" "' . $winner . '"';

                $this->update_log($str);
            }
        }
    }

    private function update_log($msg)
    {
        try
        {
            $sql = 'INSERT INTO update_log (action, date) VALUES (?, ?)';
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$msg, date('l jS \of F Y h:i:s A')]);
        }
        catch(PDOException $e)
        {
            echo "Error: " . $e->getMessage();
        }
    }
}
