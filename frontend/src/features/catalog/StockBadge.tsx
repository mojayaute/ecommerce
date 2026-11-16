export function StockBadge({ stock }: { stock: number }) {
  if (stock === 0) {
    return <span className="text-xs font-medium text-red-700">Out of stock</span>;
  }

  if (stock <= 5) {
    return <span className="text-xs font-medium text-amber-700">Only {stock} left</span>;
  }

  return <span className="text-xs text-gray-500">{stock} in stock</span>;
}
