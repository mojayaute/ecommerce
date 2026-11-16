import { Link } from 'react-router';
import { EmptyState } from '../shared/ui/feedback';

export function NotFoundPage() {
  return (
    <EmptyState>
      This page does not exist.{' '}
      <Link to="/" className="text-indigo-700 hover:underline">
        Go to the catalog
      </Link>
    </EmptyState>
  );
}
