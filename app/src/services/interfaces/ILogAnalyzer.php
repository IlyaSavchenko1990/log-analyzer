<?php

namespace app\src\services\interfaces;

use app\src\services\Options;
use Iterator;

interface ILogAnalyzer
{

    /**
     * Читает лог файл, формирует блок из строк логов для дальнейшего анализа
     * @return Iterator Итератор блоков для анализа
     */
    public function read(): Iterator;

    /**
     * Предполагает вызов метода чтения лога, перебирает блоки из строк логов,
     * анализирует по параметрам заданных опций, из найденных соответствий формирует данные
     * на вывод в консоль
     * @return Iterator Итератор с итоговыми данными анализа
     */
    public function analyze(): Iterator;
    
    /**
     * @return Options Возвращает объект настроек выполнения анализа
     */
    public function getOptions(): Options;
    
    /**
     * Назначает объект с настройками выполнения анализа
     */
    public function setOptions(Options $options): void;

}
