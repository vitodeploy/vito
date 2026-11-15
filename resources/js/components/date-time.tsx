import moment from 'moment';

export default function DateTime({
  date,
  format = 'YYYY-MM-DD HH:mm:ss',
  relative = false,
  className,
}: {
  date: string;
  format?: string;
  relative?: boolean;
  className?: string;
}) {
  return (
    <time dateTime={date} title={relative ? moment(date).format(format) : undefined} className={className}>
      {relative ? moment(date).fromNow() : moment(date).format(format)}
    </time>
  );
}
