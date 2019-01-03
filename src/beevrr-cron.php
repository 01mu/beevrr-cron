<?php
/*
 * beevrr-cron
 * github.com/01mu
 */

class beevr_cron
{
    private $conn;

    public function conn($server, $user, $pw, $db)
    {
        try
        {
            $conn = new PDO("pgsql:host=$server;dbname=$db", $user, $pw);
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
        $sql = "SELECT * FROM discussions WHERE current_phase != 'finished'";
        $stmt = $this->conn->query($sql);
        $results = $stmt->fetchAll();

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
                default:
                    $next = $result['v_end_date'];
                    $ph = 'finished';
                    break;
            }

            if(time() >= $next)
            {
                $sql = 'UPDATE discussions SET current_phase = ? WHERE id = ?';
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$ph, $id]);

                $str = 'proposition "' . $id . '" changed to ' . '"' .
                    $ph . '"';

                $this->update_log($str);

                if($ph === 'finished')
                {
                    $this->update_winner($result, $id);
                    $this->update_activities($id);
                    $this->update_active_votes($id);
                    $this->update_active_responses($id);
                    $this->update_active_discussions($id);
                }
            }
        }
    }

    private function update_activities($disc_id)
    {
        $sql = 'UPDATE activities SET is_active = 0 WHERE proposition = ?';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$disc_id]);
    }

    private function update_active_votes($disc_id)
    {
        $sql = 'SELECT user_id FROM votes WHERE proposition = ?';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$disc_id]);
        $results = $stmt->fetchAll();

        foreach($results as $result)
        {
            $sql = 'UPDATE users SET active_votes = active_votes - 1 ' .
                'WHERE id = ?';
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$result['user_id']]);
        }
    }

    private function update_active_responses($disc_id)
    {
        $sql = 'SELECT user_id FROM responses WHERE proposition = ?';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$disc_id]);
        $results = $stmt->fetchAll();

        foreach($results as $result)
        {
            $sql = 'UPDATE users SET active_responses = active_responses - 1 ' .
                'WHERE id = ?';
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$result['user_id']]);
        }
    }

    private function update_active_discussions($disc_id)
    {
        $sql = 'SELECT user_id FROM discussions WHERE id = ?';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$disc_id]);
        $results = $stmt->fetchAll();

        foreach($results as $result)
        {
            $sql = 'UPDATE users SET active_discussions = ' .
                'active_discussions - 1 WHERE id = ?';
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$result['user_id']]);
        }
    }

    private function update_winner($result, $id)
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

        $sql = 'UPDATE discussions SET winner = ? WHERE id = ?';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$winner, $id]);

        $str = 'winner set for proposition "' . $id . '" "' . $winner . '"';

        $this->update_log($str);
    }

    private function update_log($msg)
    {
        $sql = 'INSERT INTO update_log (action, date) VALUES (?, ?)';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$msg, date('l jS \of F Y h:i:s A')]);
    }

    public function clear_tables()
    {
        $cmds = ['DELETE FROM users',
            'DELETE FROM discussions',
            'DELETE FROM votes',
            'DELETE FROM activities',
            'DELETE FROM responses',
            'DELETE FROM ztext',
            'DELETE FROM update_log'];

        foreach($cmds as $cmd)
        {
            $this->conn->query($cmd);
        }
    }

    public function drop_tables()
    {
        $cmds = ['DROP TABLE users',
            'DROP TABLE discussions',
            'DROP TABLE votes',
            'DROP TABLE activities',
            'DROP TABLE responses',
            'DROP TABLE ztext',
            'DROP TABLE update_log',
            'DROP TABLE migrations'];

        foreach($cmds as $cmd)
        {
            $this->conn->query($cmd);
        }
    }
}
