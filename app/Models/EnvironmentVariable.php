<?php

namespace App\Models;

use Database\Factories\EnvironmentVariableFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $environment_id
 * @property string $key
 * @property string $value
 * @property bool $is_secret
 * @property Environment $environment
 */
class EnvironmentVariable extends AbstractModel
{
    /** @use HasFactory<EnvironmentVariableFactory> */
    use HasFactory;

    /**
     * Bash control and special variables, shared with the POSIX awk loader.
     */
    public const RESERVED_KEY_PATTERN = '^(BASH.*|CDPATH|CHILD_MAX|COLUMNS|COMP_.*|COMPREPLY|COPROC|DIRSTACK|ENV|EPOCHREALTIME|EPOCHSECONDS|EUID|EXECIGNORE|FIGNORE|FUNCNAME|FUNCNEST|GLOBIGNORE|GLOBSORT|GROUPS|'
        .'HIST(CMD|CONTROL|FILE|FILESIZE|IGNORE|SIZE|TIMEFORMAT)|HOSTFILE|IFS|IGNOREEOF|INPUTRC|LINENO|LINES|MAILCHECK|MAPFILE|OLDPWD|OPTARG|OPTERR|OPTIND|PIPESTATUS|POSIXLY_CORRECT|PPID|'
        .'PROMPT_COMMAND|PROMPT_DIRTRIM|PS[0-4]|PWD|RANDOM|READLINE_.*|REPLY|SECONDS|SHELLOPTS|SHLVL|SRANDOM|TIMEFORMAT|TMOUT|UID|_)$';

    protected $fillable = [
        'key',
        'value',
        'is_secret',
    ];

    protected $hidden = ['value'];

    protected $casts = [
        'environment_id' => 'integer',
        'value' => 'encrypted',
        'is_secret' => 'boolean',
    ];

    /**
     * @return BelongsTo<Environment, covariant $this>
     */
    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }
}
