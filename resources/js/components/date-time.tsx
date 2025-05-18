import moment from 'moment';

export default function DateTime({ date, format = 'YYYY-MM-DD hh:mm:ss', className }: { date: string; format?: string; className?: string }) {
  return (
    <time dateTime={date} className={className}>
      {moment(date).format(format)}
    </time>
  );
}
