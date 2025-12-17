<?php

namespace App\Enums;

enum ParameterType: string
{
    case TEXT = 'text';
    case NUMBER = 'number';
    case BOOLEAN = 'boolean';
    case SELECT = 'select';
    case MULTISELECT = 'multiselect';
    case DATE = 'date';
    case DATETIME = 'datetime';
    case ARRAY = 'array';
    case OBJECT = 'object';
    case FILE = 'file';
    case EMAIL = 'email';
    case URL = 'url';
    case SQL = 'sql';
    case JAVASCRIPT = 'javascript';
    case COLOR = 'color';
    case COLUMN_MAPPING = 'column_mapping';
    case SERIES_CONFIG = 'series_config';
}
