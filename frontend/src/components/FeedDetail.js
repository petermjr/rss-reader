import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { Card, Button, ListGroup } from 'react-bootstrap';
import axios from 'axios';

function FeedDetail() {
  const { id } = useParams();
  const [feed, setFeed] = useState(null);
  const [entries, setEntries] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchFeedDetails();
  }, [id]);

  const fetchFeedDetails = async () => {
    try {
      const response = await axios.get(`/api/feeds/${id}`);
      setFeed(response.data);
      setEntries(response.data.entries || []);
      setLoading(false);
    } catch (err) {
      setError('Failed to fetch feed details');
      setLoading(false);
    }
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div className="text-danger">{error}</div>;
  if (!feed) return <div>Feed not found</div>;

  return (
    <div>
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h2>{feed.title}</h2>
        <Button as={Link} to="/" variant="secondary">
          Back to Feeds
        </Button>
      </div>

      <Card className="mb-4">
        <Card.Body>
          <Card.Title>Feed Information</Card.Title>
          <Card.Text>{feed.description}</Card.Text>
          <Card.Link href={feed.url} target="_blank" rel="noopener noreferrer">
            Visit Website
          </Card.Link>
        </Card.Body>
      </Card>

      <h3>Latest Entries</h3>
      <ListGroup>
        {entries.map((entry, index) => (
          <ListGroup.Item key={index}>
            <h5>{entry.title}</h5>
            <p>{entry.description}</p>
            <small className="text-muted">
              Published: {new Date(entry.published).toLocaleDateString()}
            </small>
            {entry.link && (
              <div className="mt-2">
                <a
                  href={entry.link}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="btn btn-sm btn-outline-primary"
                >
                  Read More
                </a>
              </div>
            )}
          </ListGroup.Item>
        ))}
      </ListGroup>
    </div>
  );
}

export default FeedDetail; 