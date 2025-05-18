import { CheckIcon } from 'lucide-react';
import { Transition } from '@headlessui/react';

export default function FormSuccessful({ successful }: { successful: boolean }) {
  return (
    <Transition show={successful} enter="transition ease-in-out" enterFrom="opacity-0" leave="transition ease-in-out" leaveTo="opacity-0">
      <CheckIcon />
    </Transition>
  );
}
