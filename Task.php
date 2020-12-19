<?php

class Task
{
    const DELIMITERS = ['comma' => ',', 'semicolon' => ';'];
    const TASKS = ['countAverageLineCount', 'replaceDates'];
    const SCV_FILE_NAME = 'people.csv';
    const USER_TEXTS_PATH = 'texts/';
    const OUTPUT_TEXT_PATH = 'output_texts/';
    private string $delimiter;
    private string $task;

    public function __construct(string $delimiter, string $task)
    {
        if (key_exists($delimiter, self::DELIMITERS) && in_array($task, self::TASKS)) {
            $this->delimiter = $delimiter;
            $this->task = $task;
        } else {
            throw new Exception('Передан неправильный аргумент');
        }
    }

    public function performTask()
    {
        if ($this->task == self::TASKS[0]) {
            return $this->getAverageLineCount();
        }
        return $this->replaceDates();
    }

    protected function getCSV(string $fileName)
    {
        if (file_exists($fileName)) {
            $scvFile = fopen($fileName, 'r');
        } else {
            throw new Exception('Файла не существует');
        }
        $arrayLine = [];
        while (($data = fgetcsv($scvFile, 0, self::DELIMITERS[$this->delimiter])) !== false) {
            if ($data !== NULL) {
                $arrayLine[] = $data;
            }
        }
        //Удаление заголовков
        unset($arrayLine[0]);
        return $arrayLine;
    }

    protected function getAverageLineCount()
    {
        $result = [];
        $userData = $this->getCSV(self::SCV_FILE_NAME);
        foreach ($userData as $user) {
            if ($userTextLine = $this->getUserTexts($user[0])) {
                $countLine = [];
                $countFile = count($userTextLine);
                foreach ($userTextLine as $value) {
                    $countLine[] = count($value);
                }
            } else {
                $result[$user[1]] = 0;
                continue;
            }
            $result[$user[1]] = round(array_sum($countLine) / $countFile, 1);
        }
        return $result;
    }

    //Возвращает по id пользователя массив строк из всех его файлов
    protected function getUserTexts(int $id)
    {
        $userTextLine = [];
        $fileName = self::USER_TEXTS_PATH . $id . "-*.txt";
        if ($fileNameArray = glob($fileName)) {
            foreach ($fileNameArray as $name) {
                $userTextLine[$name] = file($name);
            }
            return $userTextLine;
        } else {
            return [];
        }
    }

    protected function replaceDates()
    {
        $pattern = '/(\d{1,2})\/(\d{1,2})\/(19|20)(\d{2})/';
        $replace = '\2-\1-\3\4';
        $result = [];
        $userData = $this->getCSV(self::SCV_FILE_NAME);
        foreach ($userData as $user) {
            $replaceCount = 0;
            if ($userTextLine = $this->getUserTexts($user[0])) {
                foreach ($userTextLine as $fileName => $value) {
                    $lineArray = [];
                    foreach ($value as $line) {
                        $lineArray[] = preg_replace($pattern, $replace, $line, $limit = -1, $count);
                        $replaceCount += $count;
                    }
                    $this->saveFile($lineArray, $fileName);
                }
                $result[$user[1]] = $replaceCount;
            } else {
                $result[$user[1]] = 0;
                continue;
            }
        }
        return $result;
    }

    protected function saveFile(array $lineArray, string $fileName)
    {
        $fileName = str_replace(self::USER_TEXTS_PATH, '', $fileName);
        $str = '';
        foreach ($lineArray as $value) {
            $str .= $value;
        }
        if (!file_exists(self::OUTPUT_TEXT_PATH)) {
            mkdir(self::OUTPUT_TEXT_PATH, 0777, true);
        }
        if (!file_exists(self::OUTPUT_TEXT_PATH . $fileName)) {
            file_put_contents(self::OUTPUT_TEXT_PATH . $fileName, $str, FILE_APPEND);
        }
    }
}
