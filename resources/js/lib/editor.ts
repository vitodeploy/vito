type monacoType = typeof import('../../../node_modules/monaco-editor/esm/vs/editor/editor.api') | null;

export function registerIniLanguage(monaco: monacoType): void {
  monaco?.languages.register({ id: 'ini' });
  monaco?.languages.setMonarchTokensProvider('ini', {
    tokenizer: {
      root: [
        [/^\[.*]$/, 'keyword'],
        [/^[^=]+(?==)/, 'attribute.name'],
        [/=.+$/, 'attribute.value'],
      ],
    },
  });
}

export function registerNginxLanguage(monaco: monacoType): void {
  monaco?.languages.register({ id: 'nginx' });
  monaco?.languages.setMonarchTokensProvider('nginx', {
    defaultToken: '',
    tokenPostfix: '.nginx',

    keywords: [
      'server',
      'location',
      'listen',
      'server_name',
      'root',
      'index',
      'charset',
      'error_page',
      'try_files',
      'include',
      'deny',
      'access_log',
      'log_not_found',
      'add_header',
      'fastcgi_pass',
      'fastcgi_param',
      'fastcgi_hide_header',
    ],

    operators: ['=', '~', '!='],

    symbols: /[=~]+/,

    tokenizer: {
      root: [
        [/#.*$/, 'comment'],

        // Block names like server, location
        [/\b(server|location)\b/, 'keyword'],

        // Keywords/directives
        [
          /\b([a-z_]+)\b(?=\s)/,
          {
            cases: {
              '@keywords': 'keyword',
              '@default': '',
            },
          },
        ],

        // Operators
        [
          /@symbols/,
          {
            cases: {
              '@operators': 'operator',
              '@default': '',
            },
          },
        ],

        // IPs, ports, URLs, filenames, values
        [/\d+\.\d+\.\d+\.\d+(:\d+)?/, 'number'],
        [/\/[^\s;"]*/, 'string.path'],
        [/\$[a-zA-Z_][\w]*/, 'variable'],
        [/".*?"/, 'string'],
        [/'.*?'/, 'string'],

        // Braces and semicolons
        [/[{}]/, 'delimiter.bracket'],
        [/;/, 'delimiter'],

        // Numbers
        [/\b\d+\b/, 'number'],
      ],
    },
  });
}

export function registerCaddyLanguage(monaco: monacoType): void {
  monaco?.languages.register({ id: 'caddy' });
  monaco?.languages.setMonarchTokensProvider('caddy', {
    defaultToken: '',
    tokenPostfix: '.caddy',

    keywords: [
      'root',
      'reverse_proxy',
      'file_server',
      'handle',
      'route',
      'redir',
      'encode',
      'tls',
      'log',
      'header',
      'php_fastcgi',
      'basicauth',
      'respond',
      'rewrite',
      'handle_path',
    ],

    operators: ['*', '=', '->'],

    symbols: /[=*>]+/,

    tokenizer: {
      root: [
        // Comments
        [/#.*$/, 'comment'],

        // Site label (e.g. example.com)
        [/^[^\s{]+(?=\s*{)/, 'type.identifier'],

        // Directives
        [
          /\b([a-z_][a-z0-9_]*)(?=\s|$)/i,
          {
            cases: {
              '@keywords': 'keyword',
              '@default': '',
            },
          },
        ],

        // Braces
        [/[{}]/, 'delimiter.bracket'],

        // Operators
        [
          /@symbols/,
          {
            cases: {
              '@operators': 'operator',
              '@default': '',
            },
          },
        ],

        // Paths, values, URIs
        [/\/[^\s#"]+/, 'string.path'],

        // Quoted strings
        [/".*?"/, 'string'],
        [/'.*?'/, 'string'],

        // Variables (environment-style)
        [/\$\{?[a-zA-Z_][\w]*\}?/, 'variable'],

        // IPs and ports
        [/\d+\.\d+\.\d+\.\d+(:\d+)?/, 'number'],
        [/\b\d{2,5}\b/, 'number'],
      ],
    },
  });
}

export function registerBashLanguage(monaco: monacoType): void {
  monaco?.languages.register({ id: 'bash' });
  monaco?.languages.setMonarchTokensProvider('bash', {
    defaultToken: '',
    tokenPostfix: '.bash',
    keywords: ['if', 'then', 'else', 'fi', 'for', 'while', 'in', 'do', 'done', 'case', 'esac', 'function', 'select', 'until', 'elif', 'time'],
    builtins: [
      'echo',
      'read',
      'cd',
      'pwd',
      'exit',
      'kill',
      'exec',
      'eval',
      'set',
      'unset',
      'export',
      'source',
      'trap',
      'shift',
      'alias',
      'type',
      'ulimit',
    ],
    tokenizer: {
      root: [
        [/#.*$/, 'comment'],
        [/"/, { token: 'string.quote', bracket: '@open', next: '@string_double' }],
        [/'/, { token: 'string.quote', bracket: '@open', next: '@string_single' }],
        [/\$[a-zA-Z_]\w*/, 'variable'],
        [/\$\{[^}]+}/, 'variable'],
        [/\b(if|then|else|fi|for|while|in|do|done|case|esac|function|select|until|elif|time)\b/, 'keyword'],
        [/\b(echo|read|cd|pwd|exit|kill|exec|eval|set|unset|export|source|trap|shift|alias|type|ulimit)\b/, 'type.identifier'],
        [/\b\d+\b/, 'number'],
        [/==|=~|!=|<=|>=|<<|>>|[<>;&|]/, 'operator'],
      ],
      string_double: [
        [/[^\\"]+/, 'string'],
        [/\\./, 'string.escape'],
        [/"/, { token: 'string.quote', bracket: '@close', next: '@pop' }],
      ],
      string_single: [
        [/[^']+/, 'string'],
        [/'/, { token: 'string.quote', bracket: '@close', next: '@pop' }],
      ],
    },
  });
}

export function registerDotEnvLanguage(monaco: monacoType): void {
  monaco?.languages.register({ id: 'dotenv' });
  monaco?.languages.setMonarchTokensProvider('dotenv', {
    tokenizer: {
      root: [
        [/#.*$/, 'comment'],
        [/^\w+/, 'variable'],
        [/=/, 'delimiter'],
        [/"[^"]*"/, 'string'],
        [/'[^']*'/, 'string'],
      ],
    },
  });
}
